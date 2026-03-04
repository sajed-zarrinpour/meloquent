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
