<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Syndicate;

/**
 * The set of product domains (agriculture / veterinary) a syndicate-style
 * workspace reads. A syndicate account always covers exactly its own domain;
 * the Ministry covers either sector or both combined. Combined queries filter
 * with `IN (...)` on the line item's resolved domain, so every order, line and
 * vendor is counted once even when it spans both sectors.
 */
final class DomainScope
{
    public const COMBINED = 'combined';

    /**
     * @param  list<string>  $types
     */
    private function __construct(
        public readonly array $types,
        public readonly string $key,
        public readonly ?Syndicate $syndicate = null,
    ) {}

    public static function forSyndicate(Syndicate $syndicate): self
    {
        return new self([$syndicate->type], $syndicate->type, $syndicate);
    }

    public static function forSector(string $sector): self
    {
        if ($sector === self::COMBINED) {
            return new self([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], self::COMBINED);
        }

        if (! in_array($sector, [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], true)) {
            throw new \InvalidArgumentException("Unknown sector [{$sector}].");
        }

        return new self([$sector], $sector);
    }

    public static function from(Syndicate|self $scope): self
    {
        return $scope instanceof self ? $scope : self::forSyndicate($scope);
    }

    /**
     * @return list<string>
     */
    public static function sectors(): array
    {
        return [self::COMBINED, Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY];
    }

    public function isCombined(): bool
    {
        return count($this->types) > 1;
    }

    /**
     * The single domain for services that only understand one, or null when
     * the scope spans every domain the platform has.
     */
    public function singleType(): ?string
    {
        return $this->isCombined() ? null : $this->types[0];
    }

    public function cacheKey(): string
    {
        return $this->syndicate
            ? "syndicate_dashboard:{$this->syndicate->id}:{$this->syndicate->type}"
            : "ministry_dashboard:{$this->key}";
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        if ($this->syndicate) {
            return [
                'id' => $this->syndicate->id,
                'name' => $this->syndicate->name,
                'type' => $this->syndicate->type,
                'type_label' => Category::typeLabels()[$this->syndicate->type] ?? $this->syndicate->type,
                'status' => $this->syndicate->status,
            ];
        }

        return [
            'id' => null,
            'name' => null,
            'type' => $this->key,
            'types' => $this->types,
            'type_label' => Category::typeLabels()[$this->key] ?? 'Combined',
            'status' => Syndicate::STATUS_ACTIVE,
        ];
    }
}
