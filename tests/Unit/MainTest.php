<?php

namespace Tests\Unit;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\TestCase;
use SajedZarinpour\Meloquent\Concerns\HasRelation;
use SajedZarinpour\Meloquent\Facades\Meloquent as FacadesMeloquent;
use SajedZarinpour\Meloquent\Meloquent;

use Illuminate\Foundation\Testing\RefreshDatabase; // to refresh the db after runing tests
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;

class MainTest extends TestCase
{
    // use RefreshDatabase;
    use DatabaseMigrations;
    use DatabaseTruncation;
    
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
}
