<?php
declare(strict_types=1);
namespace NamelessCoder\Fluid3Demo;

use TYPO3Fluid\Fluid\Component\Argument\ArgumentDefinition;
use TYPO3Fluid\Fluid\Component\ComponentInterface;
use TYPO3Fluid\Fluid\Component\EmbeddedComponentInterface;
use TYPO3Fluid\Fluid\Component\ExpressionComponentInterface;
use TYPO3Fluid\Fluid\Component\SequencingComponentInterface;
use TYPO3Fluid\Fluid\Component\TransparentComponentInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\EntryNode;
use TYPO3Fluid\Fluid\ViewHelpers\DescriptionViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\ExampleViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\SectionViewHelper;

class Introspection
{
    /**
     * @var ComponentInterface
     */
    protected $component;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var iterable|string[]
     */
    protected $examples;

    /**
     * @var iterable|SectionViewHelper[]
     */
    protected $sections;

    /**
     * @var iterable|ArgumentDefinition[]
     */
    protected $parameters;

    protected $url = '';

    protected $name = '';

    /**
     * @var Introspection|null
     */
    protected $parent;

    public function __construct(string $namespace, string $name, ComponentInterface $component)
    {
        $descriptionNode = $component->getTypedChildren(DescriptionViewHelper::class)->flatten(true);

        $this->sections = [];
        $this->examples = [];
        $this->name = $name;
        $this->namespace = $namespace;
        $this->component = $component;
        $this->description = $descriptionNode ? $descriptionNode->flatten(true) : null;
        $this->parameters = $component->getArguments()->getDefinitions();

        foreach ($component->getTypedChildren(EntryNode::class)->getChildren() as $section) {
            $sectionName = $section->getComponentName();
            $this->sections[$sectionName] = (new Introspection($namespace, $sectionName, $section))->setParent($this);
        };

        foreach ($component->getTypedChildren(ExampleViewHelper::class)->getChildren() as $index => $exampleNode) {
            $this->examples[$exampleNode->getArguments()['title']] = $exampleNode->flatten(true);
        }
    }

    /**
     * @return ComponentInterface
     */
    public function getComponent(): ComponentInterface
    {
        return $this->component;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function isEmbedded(): bool
    {
        return $this->component instanceof EmbeddedComponentInterface;
    }

    public function isExpression(): bool
    {
        return $this->component instanceof ExpressionComponentInterface;
    }

    public function isTransparent(): bool
    {
        return $this->component instanceof TransparentComponentInterface;
    }

    public function isSequencing(): bool
    {
        return $this->component instanceof SequencingComponentInterface;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        if ($this->description instanceof ComponentInterface) {
            $description = '';
            foreach ($this->description->getChildren() as $index => $child) {
                $description .= ($index > 0 ? '# Child: ' : '') . $child->getComponentName() . PHP_EOL;
                $description .= $child->flatten(true) . PHP_EOL;
            }
            return $description;
        }
        return $this->description;
    }

    /**
     * @return iterable|string[]
     */
    public function getExamples(): iterable
    {
        return $this->examples;
    }

    /**
     * @return iterable|Introspection[]
     */
    public function getSections(): iterable
    {
        return $this->sections;
    }

    public function getSection(string $name): ?Introspection
    {
        return $this->sections[$name] ?? null;
    }

    /**
     * @return iterable|ArgumentDefinition[]
     */
    public function getParameters(): iterable
    {
        return $this->parameters;
    }

    public function getPath(): string
    {
        $node = $this;
        $path = $node->getName();
        while ($parent = $node->getParent()) {
            $path = $parent->getComponentName() . '.' . $path;
            $node = $parent;
        }
        return $this->namespace . ':' . $path;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        if ($this->parent) {
            return '?component=' . $this->parent->getPath() . '&section=' . $this->getName();
        }
        return '?component=' . $this->getPath();
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getName(): string
    {
        return $this->name;
    }
}