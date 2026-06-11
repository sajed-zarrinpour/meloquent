Meloquent
==========

Microservice toolkit for Elloqeunt
-----------------------

This package provides a framework to use non persistant models (models whose data is coming from an external api) with the same functionality and syntax as laravel native models. It provides an easy way to access data through relations, and provides a clean framework to access/mutate data through api calls, while keeping core functionalities like notification broadcasting and relations.

### Installation 

Install the package using Composer:
```bash
composer require sajed-zarinpour/meloquent
```
Publish the config file of the package using the following command
```bash
php artisan vendor:publish --provider="SajedZarinpour\Meloquent\Providers\MeloquentServiceProvider"
```

Set values in the

    config/meloquent.php

### Usage

#### Relations
You can use your already defined functions to search for a value in the related models of the current model. For example
```php
class Parent extends Model 
{
    protected $fillable = [
        'id',
        'name',
    ];

    public function child() : HasMany
    {
        return $this->hasMany(Child::class);
    }
}

class Child extends Model 
{
    protected $fillable = [
        'id',
        'message',
        'parent_id',
    ];

    public function parent() : BelongsTo
    {
        return $this->belongsTo(Parent::class);
    }
}
```
instead of 
```php
echo $a->child->message;
echo $b->parent->name;
```
you can do
```php
echo $a->message;
echo $b->name;
```
To do this, you only need to add `hasRelation` trait to your models.
```php
use SajedZarinpour\Meloquent\traits\hasRelation;

class Parent extends Model 
{
    use hasRelation;

    protected $fillable = [
        'id',
        'name',
    ];

    public function child() : HasMany
    {
        return $this->hasMany(Child::class);
    }
}

class Child extends Model 
{
    use hasRelation;

    protected $fillable = [
        'id',
        'message',
        'parent_id',
    ];

    public function parent() : BelongsTo
    {
        return $this->belongsTo(Parent::class);
    }
}
```
Sure enough, the periority is with the fields defined within the model itself, if the accessed field was not in the model, we do a search for up to maximum depth set in the config to find the first match. As you have guessed, a field might be found in different relations, always the first match is returned. Also if you want to control the priority with which the relations of a model is being searched, you can do so by declaring `$priorityTable` variable. here is an example:

```php
<?php

namespace App\Models;

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
```
Note the import when using the Facade.

### Basic Config

Environement variables

    DEPTH=6


### Notification for non persistance model
When defining a new model, you can extend `BaseNonPersistanceModel`, or use your own base model with `SajedZarinpour\Meloquent\Concerns\IsNonpersistance` trait. 
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
Also You have to declare the class which is responsible with providing data by declaring `$repositioryClass` variable. Since this model is not backed by a table directly, you have to declare the primary key of it manualy by `$pKey` variable:

```php
<?php
namespace App\Models;

use App\Repositories\NonPersistanceModelRepository;
use SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel;

class NonpersistantModel extends BaseNonPersistanceModel
{
    /**
     * The class responsible for providing data, could be from api
    */
    protected static $repositioryClass = NonPersistanceModelRepository::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        // ...
    ];

    protected $pKey = [
        'field' => 'id',
        'type' => 'int',
    ];
}
```
Keep in mind that your repository class must provide its functionalities with certain fingerprint. Hence to access data you must implement `SajedZarinpour\Meloquent\Contracts\AccessRepositoryContract` 
```php
<?php
namespace SajedZarinpour\Meloquent\Contracts;

interface AccessRepositoryContract
{
    public function find();
    public function where($column, $operator='=', $value=null);
    public function refresh($pKey);
}
```
and for mutating data you must implement `SajedZarinpour\Meloquent\Contracts\MutateDataRepositoryContract`
```php
<?php
namespace SajedZarinpour\Meloquent\Contracts;
interface MutateDataRepositoryContract 
{
    public function create($attributes=[]);
    public function update($attributes=[]);
    public function delete($pKey);
}
```

and thats it. now you can use
```php
$instance = new App\Models\NonpersistantModel(['id'=>1, 'name'=>'Jack']);
$instance->notify(new App\Notifications\NotificationForNonPersistantModels);
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

class NotificationDataRepository implements CommitsNotificationToDatabaseContract
{
    public function commitNotificationToDB($attributes = [])
    {
        // code here
    }
}
```
### Reducing api calls
You might want to use redis to cache api calls data to reduce api calls. in order to do that you may add `SajedZarinpour\Meloquent\Concerns\InteractsWithRedis` to your model, this will change the behaviour of normal elloquent functions, in such way that all the mutations of the data would also affect cache, and reads are done with cache and then if no valid cache found, the api calls. No syntax changes is required:

```php
<?php

namespace App\Models;

use SajedZarinpour\Meloquent\Concerns\InteractsWithRedis;

class Agent extends Model
{
    use InteractsWithRedis;
}
```
then 
```php
// same syntax, but it automatically search for result in cache first
Agent::find(1);
// same syntax, but it also adds data in cache
$instance = Agent::create([...]);
$instance->name = 'jack';
$instance->save();
```
>[Note] Since your models are not backed by the database natively, you have to provide the id when you are creating an instance.

### Virtual Relations
Your model might be in relation with other models which are coming from an api. In order to define relations in such scenario you can use attributes:
```php
use SajedZarinpour\Meloquent\traits\hasRelation;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasMany;

class Parent extends Model 
{
    use hasRelation;

    protected $fillable = [
        'id',
        'name',
    ];

    #[MarkedAsHasMany]
    public function child()
    {
        // return a collection of child models based on your api logic
        // for example
        return Child::where('parent_id', '=', $this->id);
    }
}

//

use App\Repositories\NonPersistanceModelRepository;
use SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel;
use SajedZarinpour\Meloquent\Attributes\MarkedAsBelongsTo;

class Child extends BaseNonPersistanceModel 
{
    use hasRelation;

    protected static $repositioryClass = NonPersistanceModelRepository::class;

    protected $fillable = [
        'id',
        'message',
        'parent_id',
    ];


    protected $pKey = [
        'field' => 'id',
        'type' => 'int',
    ];

    #[MarkedAsBelongsTo]
    public function parent()
    {
        // return an instance of parent based on your app logic
        // for example
        return Parent::find($this->parent_id);
    }
}
```
