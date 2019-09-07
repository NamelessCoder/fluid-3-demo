<?php
declare(strict_types=1);

$before = microtime(true);
require_once __DIR__ . '/../vendor/autoload.php';

/*
 * Main entry point. Uses a plain old switch statement to initialize
 * the Fluid rendering given specific GET arguments.
 */
$context = new \TYPO3Fluid\Fluid\Core\Rendering\RenderingContext();
$renderer = $context->getRenderer();

$menu = [
    'Vocabulary' => '/?view=vocabulary',
    'Atoms' => '/?view=atoms',
    'Expressions' => '/?view=expressions',
    'Parsing' => '/?view=parsing',
    'ViewHelpers' => '/?view=viewhelpers',
    'PHP API' => '/?view=php',
    'Documentation' => '/?view=doc',
    'Examples' => '/?view=examples',
];


// Register the ViewHelper namespace for "demo" and add an alias "pre" which makes every <pre>
// tag get rendered by this ViewHelper, which automatically disables all Fluid parsing within
// the tag when used in tag mode. Removes the need to explicitly prevent Fluid parsing in <pre>
// since nearly all <pre> in the demo site contains Fluid example code we don't want to parse.
// And register another alias "code" to make <code> be handled by PreViewHelper as well.
$context->getViewHelperResolver()->addNamespace('demo', 'NamelessCoder\\Fluid3Demo\\ViewHelpers');
$context->getViewHelperResolver()->addViewHelperAlias('pre', 'demo', 'pre');
$context->getViewHelperResolver()->addViewHelperAlias('code', 'demo', 'pre');

// Register the templates within this demo package to make them available as pseudo-ViewHelpers
// under the "demo" namespace - which is then a combination of PHP classes and Fluid templates
// because we also registered "demo" as namespace for ViewHelpers. ViewHelpers override Atoms!
$context->getViewHelperResolver()->addAtomPath('demo', __DIR__ . '/../private/templates/');

$context->getVariableProvider()->add('url', $_SERVER['REQUEST_URI']);
$context->getVariableProvider()->add('menu', $menu);

$view = $_GET['view'] ?? null;
if (isset($_GET['component'])) {
    $view = 'component';
}

switch ($view) {
    case 'atoms':
        $file = __DIR__ . '/../private/templates/atoms.html';
        break;

    case 'expressions':
        $file = __DIR__ . '/../private/templates/expressions.html';
        break;

    case 'parsing':
        $file = __DIR__ . '/../private/templates/parsing.html';
        break;

    case 'php':
        $file = __DIR__ . '/../private/templates/php.html';
        break;

    case 'viewhelpers':
        $file = __DIR__ . '/../private/templates/viewhelpers.html';
        break;

    case 'vocabulary':
        $file = __DIR__ . '/../private/templates/vocabulary.html';
        break;

    case 'examples':
        $file = __DIR__ . '/../private/templates/examples.html';
        break;

    case 'doc':
        $lister = new \NamelessCoder\Fluid3Demo\Lister($context);
        $context->getVariableProvider()->add('lister', $lister);
        $file = __DIR__ . '/../private/templates/doc.html';
        break;

    case 'component':
        list ($namespace, $atomName) = explode(':', $_GET['component']);
        try {
            $atom = $context->getViewHelperResolver()->resolveAtom($namespace, $atomName)->setName($atomName);
        } catch (\TYPO3Fluid\Fluid\Component\Error\ChildNotFoundException $exception) {
            $atom = $context->getViewHelperResolver()->createViewHelperInstance($namespace, $atomName);
            $reflection = new \ReflectionClass($atom);

            // Emulate the presence of f:description containing the doc comment from the ViewHelper class.
            $classDocComment = $reflection->getDocComment();
            $lines = explode(PHP_EOL, $classDocComment);

            $lines = array_slice(array_map(function($line) { $trimmed = substr($line, 3); return strncmp((string)$trimmed, '@', 1) === 0 ? null : $trimmed;  }, $lines), 1, -1);
            $atom->addChild(
                (new \TYPO3Fluid\Fluid\ViewHelpers\DescriptionViewHelper())
                    ->addChild(new \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode(implode(PHP_EOL, $lines)))
            );
        }
        $introspect = new \NamelessCoder\Fluid3Demo\Introspection($namespace, $atomName, $atom);
        if ($_GET['section'] ?? false) {
            $introspect = $introspect->getSection($_GET['section']);
        }
        $context->getVariableProvider()->add('atom', $introspect);
        $before = microtime(true);
        $file = __DIR__ . '/../private/templates/component.html';
        break;

    default:
        $file = __DIR__ . '/../private/templates/index.html';
        break;
}

$initTime = microtime(true) - $before;

$before = microtime(true);
$parsed = $context->getTemplateParser()->parseFile($file);
$after = microtime(true);

$parsingTime = $after - $before;
$content = $renderer->renderFile($file);
$executionTime = microtime(true) - $after;

$content = str_replace('###parsetime###', number_format($parsingTime * 1000, 1), $content);
$content = str_replace('###exectime###', number_format($executionTime * 1000, 1), $content);
$content = str_replace('###inittime###', number_format($initTime * 1000, 1), $content);

header('Content-type: text/html');
echo $content;