<?php
declare(strict_types=1);
namespace NamelessCoder\Fluid3Demo;

use PHPStan\Reflection\ClassReflection;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\ViewHelpers\DescriptionViewHelper;

class Lister
{
    /**
     * @var RenderingContextInterface
     */
    protected $renderingContext;

    public function __construct(RenderingContextInterface $renderingContext)
    {
        $this->renderingContext = $renderingContext;
    }

    public function getAtoms(): iterable
    {
        $resolver = $this->renderingContext->getViewHelperResolver();
        $parser = $this->renderingContext->getTemplateParser();
        $atoms = [];
        foreach ($resolver->getAtoms() as $namespace => $paths) {
            foreach (array_reverse($paths) as $path) {
                /** @var \SplFileInfo[] $files */
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS));
                foreach ($files as $file) {
                    $namePart = substr($file->getPathname(), strlen($path), -5);
                    $namePart = str_replace('/', '.', $namePart);
                    $atom = $parser->parseFile($file->getPathname())->setName($namePart);
                    $atomName = $atom->getComponentName();
                    if (!isset($atoms[$atomName])) {
                        $atoms[$atomName] = new Introspection($namespace, $atomName, $atom);
                    }
                }
            }
        }
        return $atoms;
    }

    public function getViewHelpers(): iterable
    {
        $viewHelpers = [
            'f' => [
                'alias', 'case', 'comment', 'count', 'cycle', 'debug', 'defaultCase', 'description', 'else',
                'example', 'extend', 'for', 'groupedFor', 'html', 'if', 'inline', 'layout', 'or', 'parameter',
                'passthrough', 'render', 'section', 'spaceless', 'switch', 'then', 'variable',

                'cache.static',

                'expression.math', 'expression.cast',

                'format.cdata', 'format.htmlspecialchars', 'format.printf', 'format.raw',
            ],
            'demo' => [
                'pre'
            ]
        ];
        $return = [];
        foreach ($viewHelpers as $namespace => $viewHelperMethods) {
            foreach ($viewHelperMethods as $viewHelperMethod) {
                $return[$namespace . ':' . $viewHelperMethod] = $this->createViewHelperIntrospection($namespace, $viewHelperMethod);
            }
        }
        return $return;
    }

    public function createViewHelperIntrospection(string $namespace, string $method): Introspection
    {
        $resolver = $this->renderingContext->getViewHelperResolver();
        $viewHelper = $resolver->createViewHelperInstance($namespace, $method);
        return new Introspection($namespace, $method, $viewHelper);
    }
}
