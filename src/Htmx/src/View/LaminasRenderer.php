<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Htmx\View;

use Laminas\View\Exception\RenderingFailedException;
use Laminas\View\HelperPluginManagerInterface;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\RendererInterface;
use Mezzio\LaminasView\LayoutHelper;
use Mezzio\Template\ArrayParametersTrait;
use Mezzio\Template\DefaultParamsTrait;
use Mezzio\Template\Exception\InvalidArgumentException;
use Mezzio\Template\TemplateRendererInterface;

use function array_merge;
use function is_string;
use function sprintf;

/**
 * Template implementation bridging laminas/laminas-view.
 *
 * This implementation provides additional capabilities.
 *
 * First, it always ensures the resolver is an AggregateResolver, pushing any
 * non-Aggregate into a new AggregateResolver instance. Additionally, it always
 * registers a NamespacedPathStackResolver at priority 0 (lower than
 * default) in the Aggregate to ensure we can add and resolve namespaced paths.
 */
final class LaminasRenderer implements TemplateRendererInterface
{
    use ArrayParametersTrait;
    use DefaultParamsTrait;

    private ?ModelInterface $layout;

    private ?ModelInterface $body;

    /**
     * @throws InvalidArgumentException When $layout is an empty string.
     */
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly HelperPluginManagerInterface $helpers,
        string|ModelInterface|null $layout,
        string|ModelInterface|null $body,
    ) {
        if ($layout === '') {
            throw new InvalidArgumentException(sprintf('Layout must be a non-empty-string or a %s instance.', ModelInterface::class, ));
        }

        if ($body === '') {
            throw new InvalidArgumentException(sprintf('Body must be a non-empty-string or a %s instance.', ModelInterface::class, ));
        }

        if (is_string($layout)) {
            $model = new ViewModel();
            $model->setTemplate($layout);
            $layout = $model;
        }

        if (is_string($body)) {
            $model = new ViewModel();
            $model->setTemplate($body);
            $body = $model;
        }

        $this->body   = $body;
        $this->layout = $layout;
    }

    /**
     * Render a template with the given parameters.
     *
     * If a layout was specified during construction, it will be used;
     * alternately, you can specify a layout to use via the "layout"
     * parameter/variable, using either:
     *
     * - a string layout template name
     * - a Laminas\View\Model\ModelInterface instance
     *
     * Layouts specified with $params take precedence over layouts passed to
     *
     * @param non-empty-string $name
     * @param array|ModelInterface|object|null $params
     */
    public function render(string $name, $params = []): string
    {
        // layout stays the same
        // content becomes body
        // content is rendered with the provided params
        $viewModel = $params instanceof ModelInterface
            ? $params
            : new ViewModel($this->normalizeParamsAsMap($params), $name);

        $viewModel = $this->mergeViewModel($name, $viewModel);

        $body = $this->prepareBody($viewModel);

        if ($body === false) {
            // Body layer skipped — render page directly, but still wrap in layout if present
            $renderedBody = $this->renderer->render($viewModel);

            $layout = $this->prepareLayout($viewModel);

            if ($layout !== false) {
                $layout = $this->beforeRender($layout);
                $layout->setVariable('body', $renderedBody);
                $renderedBody = $this->renderer->render($layout);
            }

            $this->helpers->resetState();

            return $renderedBody;
        }

        $body->addChild(child: $viewModel, captureTo: 'content');

        $renderedBody = $this->renderRecursively($body) ?? '';

        $layout = $this->prepareLayout($body);

        if ($layout !== false) {
            $layout = $this->beforeRender($layout);
            $layout->setVariable('body', $renderedBody);
            $renderedBody = $this->renderer->render($layout);
        }

        $this->helpers->resetState();

        return $renderedBody;
    }

    /**
     * Before rendering a model, merge in any default view variables
     */
    private function beforeRender(ModelInterface $model): ModelInterface
    {
        $template = $model->getTemplate();
        if ($template === '') {
            throw RenderingFailedException::becauseATemplateWasNotSpecified();
        }

        return $this->mergeViewModel($template, $model);
    }

    /**
     * Merge global/template parameters with provided view model.
     *
     * @param non-empty-string $name Template name.
     */
    private function mergeViewModel(string $name, ModelInterface $model): ModelInterface
    {
        $model->setVariables($this->mergeParams(
            $name,
            $model->getVariables(),
        ));

        $model->setTemplate($name);

        return $model;
    }

    /**
     * Prepare the layout, if any.
     *
     * Injects the view model in the layout view model, if present.
     *
     * If the view model contains a non-empty 'layout' variable, that value
     * will be used to seed a layout view model, if:
     *
     * - it is a string layout template name
     * - it is a ModelInterface instance
     *
     * If a layout is discovered in this way, it will override the one set in
     * the constructor, if any.
     *
     * Returns the provided $viewModel unchanged if no layout is discovered;
     * otherwise, a view model representing the layout, with the provided
     * view model as a child, is returned.
     */
    private function prepareLayout(ModelInterface $viewModel): ModelInterface|false
    {
        /** @psalm-var mixed $providedLayout */
        $providedLayout = $viewModel->getVariable('layout', null);

        // When the layout is explicitly given as false in the top-level view model, then layout will be disabled.
        if ($providedLayout === false) {
            return false;
        }

        /**
         * In all other situations, layout is defined in the following order:
         *
         * - Layout defined by the layout view helper
         * - layout defined in the params of the given view model ($providedLayout)
         * - The default layout defined in $this->layout
         * - no layout
         */
        $helperLayout = $this->helpers->get(LayoutHelper::class)->__invoke();
        if ($helperLayout->getTemplate() !== '') {
            return $helperLayout;
        }

        $variables = $helperLayout->getVariables();

        if (is_string($providedLayout) && $providedLayout !== '') {
            return new ViewModel($variables, $providedLayout);
        }

        if ($providedLayout instanceof ModelInterface && $providedLayout->getTemplate() !== '') {
            return new ViewModel(array_merge(
                $providedLayout->getVariables(),
                $variables,
            ), $providedLayout->getTemplate());
        }

        if ($this->layout instanceof ModelInterface) {
            return new ViewModel(array_merge(
                $this->layout->getVariables(),
                $variables,
            ), $this->layout->getTemplate());
        }

        return false;
    }

    /** @throws RenderingFailedException When any exception occurs during render. */
    private function renderRecursively(ModelInterface $model): string
    {
        foreach ($model->getChildren() as $child) {
            $content = $this->renderRecursively($child);
            if ($child->isAppend()) {
                /** @psalm-var mixed $existingContent */
                $existingContent = $model->getVariable($child->captureTo(), '');
                $existingContent = is_string($existingContent)
                    ? $existingContent
                    : '';

                $content = $existingContent . $content;
            }

            $model->setVariable($child->captureTo(), $content);
        }

        return $this->renderer->render($this->beforeRender($model));
    }

    /**
     * Prepare the body layer, if any.
     *
     * If the page view model contains a 'body' variable explicitly set to false,
     * the body layer is skipped and the page template is rendered directly.
     * This mirrors the behaviour of prepareLayout() for the layout layer.
     */
    private function prepareBody(ModelInterface $viewModel): ModelInterface|false
    {
        /** @psalm-var mixed $providedBody */
        $providedBody = $viewModel->getVariable('body', null);

        if ($providedBody === false) {
            return false;
        }

        return $this->body;
    }
}
