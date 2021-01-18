<?php

namespace Phel\Compiler\Parser\ParserNode;

use Phel\Lang\SourceLocation;

final class MetaNode implements InnerNodeInterface
{
    private NodeInterface $meta;
    /** @var NodeInterface[] */
    private array $children;
    private SourceLocation $startLocation;
    private SourceLocation $endLocation;

    public function __construct(NodeInterface $meta, SourceLocation $startLocation, SourceLocation $endLocation, array $children)
    {
        $this->meta = $meta;
        $this->children = $children;
        $this->startLocation = $startLocation;
        $this->endLocation = $endLocation;
    }

    public function getChildren(): array
    {
        return [$this->meta, ...$this->children];
    }

    public function replaceChildren($children): InnerNodeInterface
    {
        $this->meta = $children[0];
        $this->children = array_slice($children, 1);

        return $this;
    }

    public function getCode(): string
    {
        $code = '';
        foreach ($this->children as $child) {
            $code .= $child->getCode();
        }
        return $this->getCodePrefix() . $this->meta->getCode() . $code;
    }

    public function getCodePrefix(): string
    {
        return '^';
    }

    public function getCodePostfix(): ?string
    {
        return null;
    }

    public function getStartLocation(): SourceLocation
    {
        return $this->startLocation;
    }

    public function getEndLocation(): SourceLocation
    {
        return $this->endLocation;
    }

    public function getMetaNode(): NodeInterface
    {
        return $this->meta;
    }

    public function getObjectNode(): NodeInterface
    {
        return $this->children[count($this->children) - 1];
    }
}
