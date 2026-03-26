Meloquent
==========

Mirrors for Elloqeunt
-----------------------

This package provides some functionalities over elloquent objects using mirrors (hence the name).

### Installation 

Step 1:

Install the package using Composer:

    composer require sajed-zarinpour/meloquent

Step 2:

Publish the config file of the package using the following command

    php artisan vendor:publish --provider="SajedZarinpour\Meloquent\Providers\MeloquentServiceProvider"

Step 3:

Set values in the

    config/meloquent.php

### Usage

trait:

    <?php
    
    namespace App\Models;
    
    use Illuminate\Http\Request;
    
    use SajedZarinpour\Meloquent\traits\hasRelation;
    
    class Agent extends Model
    {
        use hasRelation;

        protected static array $priorityTable = [
            HasMany::class,
            BelongsTo::class,
            ...
        ];
    }

Note the import when using the Facade.

### Basic Usage

Environement variables

    DEPTH=6


### Notification for non persistance model
define your model
```php
<?php
namespace App\Models;

use App\Repositories\NonPersistanceModelRepository;
use SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel;

class NonpersistantModel extends BaseNonPersistanceModel
{
    protected static $repositioryClass = NonPersistanceModelRepository::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'date_of_birth',
        'is_active',
    ];

    protected $pKey = [
        'field' => 'id',
        'type' => 'int',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'date_of_birth' => 'date',
    ];
}
```
and thats it. now you can use
```php
$user = new App\Models\NonpersistantModel(['id'=>1, 'name'=>'Jack']);
$user->notify(new App\Notifications\NotificationForNonPersistantModels);
```
note that if you want to use database chanel in your notification, you can either implement `CommitsNotificationToDatabaseContract` in your model, or define a `$notificationDataRepositoryClass` class. Be aware that the implementation inside the class would have the priority. 
then your notification looks something like
```php
<?php

namespace App\Notifications;

use App\Broadcasting\MyCustomNotifyChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationForNonPersistantModels extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [MyCustomNotifyChannel::class, 'database'];
    }

    public function toDatabase(object $notifiable)
    {
        return [
            'data'=> true
        ];
    }

    public function toMyCustomNotification(object $notifiable)
    {
        return [
            'notifiable'=> $notifiable
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
```
and sure enough, if you are using a repo then:
```php
<?php
namespace App\Repositories;

use SajedZarinpour\Meloquent\Contracts\CommitsNotificationToDatabaseContract;

class NotificationDataRepository implements CommitsNotificationToDatabaseContract{
    public function commitNotificationToDB($attributes = [])
    {
        // code here
    }
}
```
The base model is a simple model declared as
```php
<?php

namespace SajedZarinpour\Meloquent\Models;

use Illuminate\Database\Eloquent\Model;
use SajedZarinpour\Meloquent\Concerns\IsNonpersistance;

class BaseNonPersistanceModel extends Model implements \ArrayAccess
{
    use IsNonpersistance;
    
    /**
     * Implement ArrayAccess to allow array access to attributes
     * This allows $instance['attribute'] syntax to work
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]) || isset($this->$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->attributes[$offset] ?? $this->$offset ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }
}
```
you might have your own base model and doesn't want to extend this base model:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SajedZarinpour\Meloquent\Concerns\IsNonpersistance;

class BaseModel extends Model
{
    use IsNonpersistance;
    // ...
}

```