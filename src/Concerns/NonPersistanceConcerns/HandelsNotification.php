<?php
namespace SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use SajedZarinpour\Meloquent\Contracts\CommitsNotificationToDatabaseContract;
use SajedZarinpour\Meloquent\Jobs\SendVirtualNotification;
use SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel;

trait HandelsNotification {

    use  Notifiable;

    /**
     * Override notify method to handle notifications for virtual model.
     *
     * Handles: database, broadcast, custom channel classes, queued notifications.
     */
    public function notify($notification)
    {
        try {
            // Resolve channels: ensure notification->via returns string names and class names
            $rawChannels = $notification->via($this) ?? [];
            $channels = collect($rawChannels)
                ->map(fn($c) => is_object($c) ? get_class($c) : $c)
                ->map(fn($c) => (string) $c)
                ->unique()
                ->values()
                ->all();

            // If notification is queued (implements ShouldQueue) prefer dispatching to queue
            if ($notification instanceof ShouldQueue) {
                // Extract serializable constructor args from $notification.
                $notificationClass = get_class($notification);

                // Build notification constructor args — make sure they are primitives/arrays only.
                $notificationArgs = $this->extractNotificationArgs($notification);

                $notifiableData = [
                    'id' => $this->id ?? null,
                    'notifiable_type' => static::class,
                    'channels' => $channels,
                ];

                // Optional: if your notification defines a $queue property, honor it
                $queueName = property_exists($notification, 'queue') ? ($notification->queue ?? null) : null;

                SendVirtualNotification::dispatch($notificationClass, $notificationArgs, $notifiableData, $queueName)
                    ->afterCommit();

                return;
            }

            // If not queued, process immediately
            $this->processNotificationNow($notification);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Extract Notification constructor arguements using reflection
     */
    protected function extractNotificationArgs(object $notification): array
    {
        $class = get_class($notification);

        try {
            $ref = new ReflectionClass($notification);
            $ctor = $ref->getConstructor();
            if (! $ctor) {
                return [];
            }

            $args = [];

            /** @var ReflectionParameter[] $params */
            $params = $ctor->getParameters();

            // Build a lookup of notification public properties for quick access
            $publicProps = [];
            foreach ($ref->getProperties() as $prop) {
                if ($prop->isPublic()) {
                    $propName = $prop->getName();
                    $publicProps[$propName] = $prop->getValue($notification);
                }
            }

            // Also allow reading via getter methods
            $methods = [];
            foreach ($ref->getMethods() as $m) {
                $methods[strtolower($m->getName())] = $m;
            }

            foreach ($params as $p) {
                $name = $p->getName();

                $value = null;
                $found = false;

                // 1) public property matching param name
                if (array_key_exists($name, $publicProps)) {
                    $value = $publicProps[$name];
                    $found = true;
                }

                // 2) public property with "Payload" suffix (e.g., feedPayload for $feed)
                if (! $found && array_key_exists($name . 'Payload', $publicProps)) {
                    $value = $publicProps[$name . 'Payload'];
                    $found = true;
                }

                // 3) method get<Name>() or name()
                if (! $found) {
                    $getter1 = 'get' . ucfirst($name);
                    $getter2 = $name;
                    if (isset($methods[strtolower($getter1)]) && $methods[strtolower($getter1)]->isPublic()) {
                        $value = $notification->{$getter1}();
                        $found = true;
                    } elseif (isset($methods[strtolower($getter2)]) && $methods[strtolower($getter2)]->isPublic()) {
                        $value = $notification->{$getter2}();
                        $found = true;
                    }
                }

                // 4) if still not found, try any public property that matches by suffix (e.g., receiver_id vs receiverId)
                if (! $found) {
                    foreach ($publicProps as $k => $v) {
                        if (strcasecmp($k, $name) === 0 || strcasecmp($this->snakeOrCamel($k), $this->snakeOrCamel($name)) === 0) {
                            $value = $v;
                            $found = true;
                            break;
                        }
                    }
                }

                // 5) final fallback: if param has default, use it; else null
                if (! $found) {
                    if ($p->isDefaultValueAvailable()) {
                        $value = $p->getDefaultValue();
                    } else {
                        $value = null;
                    }
                }

                // Now ensure value is serializable/primitives/arrays
                $serializable = $this->makeSerializable($value, $class, $name);

                $args[] = $serializable;
            }

            return $args;
        } catch (\Throwable $e) {
            Log::error('extractNotificationArgs reflection failure', ['class' => $class, 'err' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Convert common complex types into serializable primitives/arrays.
     */
    protected function makeSerializable(mixed $value, string $notificationClass, string $paramName): mixed
    {
        // null/scalar/array OK
        if (is_null($value) || is_scalar($value) || is_array($value)) {
            return $value;
        }

        // Eloquent Model => toArray
        if ($value instanceof BaseNonPersistanceModel) {
            try {
                return $value->toArray();
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        // Eloquent Collection => toArray
        if ($value instanceof Collection) {
            return $value->toArray();
        }

        // If it's an object with toArray method, try it
        if (is_object($value) && method_exists($value, 'toArray')) {
            try {
                return $value->toArray();
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        // Skip closures, builders, DB connections, etc.
        $type = is_object($value) ? get_class($value) : gettype($value);

        return null;
    }

    /**
     * Extracted synchronous processing to keep notify() lean.
     */
    protected function processNotificationNow($notification)
    {
        $now = now();

        // Resolve channels robustly
        $rawChannels = $notification->via($this) ?? [];
        $channels = collect($rawChannels)
            ->map(fn($c) => is_object($c) ? get_class($c) : $c)
            ->map(fn($c) => (string) $c)
            ->unique()
            ->values()
            ->all();

        // DATABASE
        if (in_array('database', $channels, true)) {

            try {

                $notifiableData = (object)[
                    'id' => $this->id ?? null,
                    'notifiable_type' => static::class,
                    'channels' => $channels,
                ];
                $data = $notification->toDatabase($notifiableData);

                $instance = new $notifiableData->notifiable_type;
                $reflection = new \ReflectionObject($instance);
                
                if (in_array(CommitsNotificationToDatabaseContract::class, $reflection->getInterfaceNames())) {
                    $instance->commitNotificationToDB($data);
                } else {
                    $class = (
                        new \ReflectionProperty(static::class, 'notificationDataRepositoryClass')
                    )->getValue();
            
                    $userDefinedNotificationDataRepository = app($class);
                    $userDefinedNotificationDataRepositoryReflection = new \ReflectionObject($userDefinedNotificationDataRepository);

                    if (in_array(CommitsNotificationToDatabaseContract::class, $userDefinedNotificationDataRepositoryReflection->getInterfaceNames())){
                        $userDefinedNotificationDataRepository->commitNotificationToDB($data);
                    } else {
                        throw new Exception('your notification repo must implements CommitsNotificationToDatabaseContract');
                    }
                }
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        // BROADCAST
        if (in_array('broadcast', $channels, true)) {
            try {
                $broadcastPayload = $notification->toBroadcast($this);
                if ($broadcastPayload) {
                    event($broadcastPayload);
                }
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        // Custom channel classes (e.g., app-specific channels in app/Broadcasting/)
        // Allow custom channels to receive the notifiable and notification.
        $customChannels = array_filter($channels, fn($c) => $c !== 'database' && $c !== 'broadcast');

        foreach ($customChannels as $channel) {
            try {
                // If the channel is a class name, instantiate and call send()
                if (class_exists($channel)) {
                    $chanInstance = app($channel);
                    if (method_exists($chanInstance, 'send')) {
                        $chanInstance->send($this, $notification);
                    } elseif (method_exists($chanInstance, 'broadcast')) {
                        $chanInstance->broadcast($this, $notification);
                    } else {
                        Log::warning('notify() unknown channel method', ['channel' => $channel]);
                    }
                } else {
                    Log::warning('notify() channel class not found', ['channel' => $channel]);
                }
            } catch (\Throwable $e) {
                throw $e;
            }
        }
    }
}