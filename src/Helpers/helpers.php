<?php

use SajedZarinpour\Meloquent\Meloquent;
use SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel;

use SajedZarinpour\Meloquent\Attributes\MarkedAsBelongsTo;
use SajedZarinpour\Meloquent\Attributes\MarkedAsBelongsToMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasManyThrough;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasOne;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasOneOrMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasOneOrManyThrough;
use SajedZarinpour\Meloquent\Attributes\MarkedAsHasOneThrough;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphOne;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphOneOrMany;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphTo;
use SajedZarinpour\Meloquent\Attributes\MarkedAsMorphToMany;

/**
 * Adding helper functionality to the package. The following calls are equivalent.
 * ```
 *      meloquent::some_func()
 * ```
 * vs
 * ```
 *      meloquent()->some_func()
 * ```
 */
if (!function_exists('meloquent')) {
    function meloquent(?array $priorityTable=[]): Meloquent
    {
        return new Meloquent($priorityTable);
    }
}

if(!function_exists('getModelRelationAttributes')){
    function getModelRelationAttributes(BaseNonPersistanceModel $model){
        $reflection = new ReflectionObject($model);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $list = [];
            foreach ($methods as $method) {
                $attributes = $method->getAttributes();
                if (count($attributes)>0) {
    
                    $attrs = array_filter($attributes, static function ($attribute) {
                        return match ($attribute->name) {
                            MarkedAsBelongsTo::class,
                            MarkedAsBelongsToMany::class,
                            MarkedAsHasMany::class,
                            MarkedAsHasManyThrough::class,
                            MarkedAsHasOne::class,
                            MarkedAsHasOneOrMany::class,
                            MarkedAsHasOneOrManyThrough::class,
                            MarkedAsHasOneThrough::class,
                            MarkedAsMorphMany::class,
                            MarkedAsMorphOne::class,
                            MarkedAsMorphOneOrMany::class,
                            MarkedAsMorphTo::class,
                            MarkedAsMorphToMany::class=>true,
                            default => false,
                        };
                    });

                    if (sizeof($attrs)>0) {
                        $list [] = [
                            'method' => $method,
                            'relations'=>$attrs
                        ];
                    }
                    
                }
            }

            return $list;
    }
}

if(!function_exists('yare')){
    function yare()
    {
        $parentModelFactory = function() {
            return new class extends SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel{
                use SajedZarinpour\Meloquent\Concerns\HasRelation;
                public static int $idGenerator = 1;

                public $c;

                protected $fillable = [
                    'id',
                    'name',
                ];

                protected $pKey = [
                    'field' => 'id',
                    'type' => 'int',
                ];
    
                public function __construct() 
                {
                    $this->_setId();
                    $this->name = 'parent';
                }

                public function setChild($c)
                {
                    $this->c = $c;
                }
    
                #[MarkedAsHasMany]
                public function child() 
                {
                    // return $this->hasMany($this->c::class, 'id');
                    return $this->c;
                }

                private function _setId()
                {    
                    $this->id = self::$idGenerator++;
                }
            };
        };
    
        $childModelFactory = function() {
            return new class extends SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel{
                use SajedZarinpour\Meloquent\Concerns\HasRelation;
                public static int $idGenerator = 1;

                protected $fillable = [
                    'id',
                    'name',
                    'message',
                    'parent_id'
                ];

                protected $pKey = [
                    'field' => 'id',
                    'type' => 'int',
                ];
    
                public $p;
    
                public function __construct() { 
                    $this->id = self::$idGenerator++;
                }
    
                public function setParent($p)
                {
                    $this->parent_id = $p->id;
                    $this->p = $p;
                }
    
                #[MarkedAsBelongsTo]
                public function parent() {
                    // return $this->belongsTo($this->p::class, 'parent_id');
                    return $this->p;
                }
            };
        };
    
        $a = $parentModelFactory();
        $b = $childModelFactory();
        $b->message = 'period';
    
        $b->setParent($a);
        $a->setChild($b);

        return $a->message;
    }

}
