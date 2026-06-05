<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SajedZarinpour\Meloquent\Facades\Meloquent as FacadesMeloquent;
use SajedZarinpour\Meloquent\Meloquent;
use SajedZarinpour\Meloquent\Concerns\HasRelation;

class MainTest extends TestCase
{
    
    /**
     * A basic test example.
     */
    public function test_that_helper_function_works_properly(): void
    {
        $instance = meloquent();
        $this->assertInstanceOf(Meloquent::class, $instance, 'helper function returned: '.$instance::class);
    }

    public function test_that_facade_works_properly(): void
    {
        // this is basically a dumb test, nonetheless it contains some value
        FacadesMeloquent::swap(new Meloquent([]));
        $instance = FacadesMeloquent::getFacadeRoot();
        $this->assertInstanceOf(Meloquent::class, $instance, 'facade returned: '.$instance::class);
    }

    public function test_syntax_consistency():void
    {

        $parentModelFactory = function() {
            return new class extends \SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel{
                use \SajedZarinpour\Meloquent\Concerns\HasRelation;
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
    
                #[\SajedZarinpour\Meloquent\Attributes\MarkedAsHasMany]
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
            return new class extends \SajedZarinpour\Meloquent\Models\BaseNonPersistanceModel{
                use \SajedZarinpour\Meloquent\Concerns\HasRelation;
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
    
                #[\SajedZarinpour\Meloquent\Attributes\MarkedAsBelongsTo]
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

        $this->assertEquals($b->message, $a->message);

    }
}
