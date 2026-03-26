<?php

namespace SajedZarinpour\Meloquent\Contracts;

interface CommitsNotificationToDatabaseContract 
{
    public function commitNotificationToDB($attributes=[]);
}