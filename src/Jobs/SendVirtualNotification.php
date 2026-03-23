<?php

namespace SajedZarinpour\Meloquent\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionObject;
use SajedZarinpour\Meloquent\Contracts\CommitsNotificationToDatabaseContract;

class SendVirtualNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $notificationClass;
    public array $notificationArgs;     // constructor args for the Notification (primitives)
    public array $notifiableData;      // id, organization_id, channels, etc.
    public ?string $queueName;

    public function __construct(string $notificationClass, array $notificationArgs, array $notifiableData, ?string $queueName = null)
    {
        $this->notificationClass = $notificationClass;
        $this->notificationArgs = $notificationArgs;
        $this->notifiableData = $notifiableData;
        $this->queueName = $queueName;
        if ($this->queueName) {
            $this->onQueue($this->queueName);
        }
    }

    public function handle()
    {
        info('calles');
        // instantiate notification with primitive args
        $notification = new ($this->notificationClass)(...$this->notificationArgs);

        // Process channels similarly to your virtual notify implementation,
        // but pass $this->notifiableData as the "notifiable" context where needed.
        $channels = $notification->via((object)$this->notifiableData) ?? [];

        if (in_array('database', $channels, true)) {
            $data = $notification->toDatabase((object)$this->notifiableData);

            $instance = new $this->notifiableData['notifiable_type'];
            $reflection = new \ReflectionObject($instance);
            
            if (in_array(CommitsNotificationToDatabaseContract::class, $reflection->getInterfaceNames())) {
                $instance->commitNotificationToDB($data);
            } else {
                $class = (
                    new \ReflectionProperty($this->notifiableData['notifiable_type'], 'notificationDataRepositoryClass')
                )->getValue();
        
                $userDefinedNotificationDataRepository = app($class);
                $userDefinedNotificationDataRepositoryReflection = new \ReflectionObject($userDefinedNotificationDataRepository);

                if (in_array(CommitsNotificationToDatabaseContract::class, $userDefinedNotificationDataRepositoryReflection->getInterfaceNames())){
                    $userDefinedNotificationDataRepository->commitNotificationToDB($data);
                } else {
                    throw new Exception('your notification repo must implements CommitsNotificationToDatabaseContract');
                }
            }
        }

        if (in_array('broadcast', $channels, true)) {
            $payload = $notification->toBroadcast((object)$this->notifiableData);
            if ($payload) {
                event($payload);
            }
        }

        // custom channels
        $custom = array_filter($channels, fn($c) => $c !== 'database' && $c !== 'broadcast');
        foreach ($custom as $channel) {
            if (class_exists($channel)) {
                $chan = app($channel);
                if (method_exists($chan, 'send')) {
                    $chan->send((object)$this->notifiableData, $notification);
                } elseif (method_exists($chan, 'broadcast')) {
                    $chan->broadcast((object)$this->notifiableData, $notification);
                }
            }
        }
    }
}
