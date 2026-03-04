<?php
namespace SajedZarinpour\Meloquent\DTOs;

use Closure;

final class RelationFieldInfoDto
{
    public string $ownerModelClass;
    public string $ownerPrimaryKey;
    public ?string $foreignKeyOnOrigin;
    /** @var string[] */
    public array $relationPath;
    public ?Closure $resolver; // optional callable to resolve values

    /**
     * @param string[] $relationPath
     */
    public function __construct(
        string $ownerModelClass,
        string $ownerPrimaryKey,
        ?string $foreignKeyOnOrigin,
        array $relationPath = [],
        ?Closure $resolver = null
    ) {
        $this->ownerModelClass = $ownerModelClass;
        $this->ownerPrimaryKey = $ownerPrimaryKey;
        $this->foreignKeyOnOrigin = $foreignKeyOnOrigin;
        $this->relationPath = $relationPath;
        $this->resolver = $resolver;
    }

    public static function fromArray(array $a): self
    {
        return new self(
            $a['owner_model_class'],
            $a['owner_primary_key'],
            $a['foreign_key_on_origin'] ?? null,
            $a['relation_path'] ?? [],
            $a['resolver'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'owner_model_class' => $this->ownerModelClass,
            'owner_primary_key' => $this->ownerPrimaryKey,
            'foreign_key_on_origin' => $this->foreignKeyOnOrigin,
            'relation_path' => $this->relationPath,
            'resolver' => $this->resolver,
        ];
    }

    /**
     * Example: returns a Closure that, given an origin model instance,
     * will traverse the relationPath and return the final related model or null.
     */
    public function makeResolver(): Closure
    {
        return $this->resolver ?? function ($originModel) {
            $current = $originModel;
            foreach ($this->relationPath as $rel) {
                if (! $current) return null;
                // prefer loaded relation first, then call relation method
                if ($current->relationLoaded($rel)) {
                    $current = $current->getRelation($rel);
                } elseif (method_exists($current, $rel)) {
                    $current = $current->{$rel}();
                    // if relation builder returned a Relation, get the related model(s) appropriately
                    if ($current instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        $related = $current->getRelated();
                        // don't call getResults() to avoid queries; instead attempt to access loaded relation
                        // fallback: eager load single relation
                        try {
                            $current = $current->getResults(); // may execute query
                        } catch (\Throwable $e) {
                            $current = null;
                        }
                    }
                } else {
                    return null;
                }

                // If relation returns a Collection, get first model for traversal
                if ($current instanceof \Illuminate\Support\Collection) {
                    $current = $current->first();
                }
            }
            return $current;
        };
    }
}
