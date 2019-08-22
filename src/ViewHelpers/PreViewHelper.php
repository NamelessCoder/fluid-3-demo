<?php
namespace NamelessCoder\Fluid3Demo\ViewHelpers;

use TYPO3Fluid\Fluid\Component\AbstractComponent;
use TYPO3Fluid\Fluid\Component\Argument\ArgumentDefinition;
use TYPO3Fluid\Fluid\Component\ComponentInterface;
use TYPO3Fluid\Fluid\Component\SequencingComponentInterface;
use TYPO3Fluid\Fluid\Core\Parser\Sequencer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class PreViewHelper extends AbstractComponent implements SequencingComponentInterface
{
    protected $escapeOutput = false;

    protected $method;

    public function onOpen(RenderingContextInterface $renderingContext): ComponentInterface
    {
        $this->getArguments()
            ->setRenderingContext($renderingContext)
            ->addDefinition(
                new ArgumentDefinition('content', 'mixed', 'Content of tag, if not passed as tag body', false)
            );
        return parent::onOpen($renderingContext);
    }

    public function evaluate(RenderingContextInterface $renderingContext)
    {
        $arguments = $this->getArguments()->setRenderingContext($renderingContext)->getArrayCopy();
        $content = htmlspecialchars($arguments['content'] ?? parent::evaluate($renderingContext));
        unset($arguments['content']);
        $tagBuilder = new TagBuilder($this->method);
        $tagBuilder->addAttributes($arguments);
        $tagBuilder->setContent($content);
        $tagBuilder->forceClosingTag(true);
        return $tagBuilder->render();
    }

    public function sequence(Sequencer $sequencer, ?string $namespace, string $method): void
    {
        $this->method = $method;
        $sequencer->sequenceUntilClosingTagAndIgnoreNested($this, $namespace, $method);
    }
}