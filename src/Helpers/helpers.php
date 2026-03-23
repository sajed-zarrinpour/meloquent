<?php

use SajedZarinpour\Meloquent\Meloquent;
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


// function yare()
// {
//         $parentModelFactory = function() {
//             return new class extends Illuminate\Database\Eloquent\Model{
//                 use SajedZarinpour\Meloquent\Concerns\HasRelation;
//                 public static int $idGenerator = 1;
//                 public int $id;
//                 public string $name;

//                 public $c;

//                 public function __construct() { 
//                     $this->id = self::$idGenerator++;
//                     $this->name = 'parent';
//                 }

//                 public function setChild($c)
//                 {
//                     $this->c = $c;
//                 }

//                 public function child() {
//                     return $this->hasMany($this->p::class, 'id');
//                 }

//                 public function __get($property)
//                 {
//                     try {
//                         return parent::__get($property);
//                     } catch (\Throwable $th) {
//                         if ($th instanceof Illuminate\Database\QueryException) {
//                             return $this->c;
//                         }
//                     }  
//                 }
//             };
//         };

//         $childModelFactory = function() {
//             return new class extends Illuminate\Database\Eloquent\Model{
//                 use SajedZarinpour\Meloquent\Concerns\HasRelation;
//                 public static int $idGenerator = 1;
//                 public int $id;
//                 public int $parent_id;
//                 public string $name;
//                 public string $message;

//                 public $p;

//                 public function __construct() { 
//                     $this->id = self::$idGenerator++;
//                 }

//                 public function setParent($p)
//                 {
//                     $this->parent_id = $p->id;
//                     $this->p = $p;
//                 }

//                 public function parent() {
//                     return $this->belongsTo($this->p::class, 'parent_id');
//                 }

//                 public function __get($property)
//                 {
//                     try {
//                         return parent::__get($property);
//                     } catch (\Throwable $th) {
//                         if ($th instanceof Illuminate\Database\QueryException) {
//                             return $this->p;
//                         }
//                     }  
//                 }
//             };
//         };

//         $a = $parentModelFactory();
//         $b = $childModelFactory();
//         $b->message = 'period';

//         $b->setParent($a);
//         $a->setChild($b);

//         var_dump($a->get_('message'));

// }
