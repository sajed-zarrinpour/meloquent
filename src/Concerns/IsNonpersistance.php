<?php
namespace SajedZarinpour\Meloquent\Concerns;


use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\CRUD;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\DataFetching;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\DateUtils;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\HandelsNotification;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\Metadata;
use SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns\TableStructure;

trait IsNonpersistance {

    /**
     * structural concerns:
    */
    use TableStructure;
    
    /**
     * meta data
     */
    use Metadata;

    /**
     * Date 
     */
    use DateUtils;

    /**
     * Data Fetching
     */
    use DataFetching;

    /**
     * handling notifications
     */
    use HandelsNotification;

    /**
     * CRUD
     */
    use CRUD;
}