<?php

namespace App\SEO\Schema;

class SchemaGraph
{
    /**
     * @var list<array<string, mixed>>
     */
    protected array $nodes = [];

    /**
     * @param  array<string, mixed>|null  $node
     */
    public function add(?array $node): self
    {
        $node = $this->clean($node);

        if (! is_array($node) || $node === []) {
            return $this;
        }

        $this->nodes[] = $node;

        return $this;
    }

    /**
     * @param  iterable<array<string, mixed>|null>  $nodes
     */
    public function addMany(iterable $nodes): self
    {
        foreach ($nodes as $node) {
            $this->add($node);
        }

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function nodes(): array
    {
        $nodes = [];
        $idPositions = [];

        foreach ($this->nodes as $node) {
            $id = $node['@id'] ?? null;

            if (is_string($id) && $id !== '') {
                if (array_key_exists($id, $idPositions)) {
                    $position = $idPositions[$id];
                    $nodes[$position] = array_replace_recursive($nodes[$position], $node);

                    continue;
                }

                $idPositions[$id] = count($nodes);
            }

            $nodes[] = $node;
        }

        return array_values($nodes);
    }

    protected function clean(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $cleaned = [];

        foreach ($value as $key => $item) {
            $item = $this->clean($item);

            if ($item === null || $item === '' || $item === []) {
                continue;
            }

            $cleaned[$key] = $item;
        }

        return $cleaned;
    }
}
