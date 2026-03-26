<?php
namespace SajedZarinpour\Meloquent\Attributes;

/**
 * it is very important to understand how naming for these attributes works,
 * for any relations in elloquent we have an equivalent
 * prefix + elloquent name
 * the prefix would be the name of the class which is defined here.
 * so for example for HasOne, we would have
 * MarkedAs + HasOne.
 * 
 * see getsortNonPersistantRelationsMarkedByAttributesPriorityTable to find out why this is important
 */
class MarkedAs {}