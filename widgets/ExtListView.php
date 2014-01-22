<?php

namespace amnah\yii2\widgets;

use Closure;

class ExtListView extends \yii\widgets\ListView {

    /**
     * @var string|Closure The layout view or closure
     *
     * In the view file, use the same tokens:
     * - `{summary}`: the summary section. See [[renderSummary()]].
     * - `{items}`: the list items. See [[renderItems()]].
     * - `{sorter}`: the sorter. See [[renderSorter()]].
     * - `{pager}`: the pager. See [[renderPager()]].
     */
    public $layoutView;

    /**
     * @var array additional parameters to be passed to [[layoutView]] when it is being rendered
     */
    public $layoutViewParams = [];

    /**
     * @var string|Closure The empty view or closure
     */
    public $emptyView;

    /**
     * @var array additional parameters to be passed to [[emptyView]] when it is being rendered
     */
    public $emptyViewParams = [];

    /**
     * @inheritdoc
     */
    public function run() {

        // run normal parent implementation if view is not set
        if (empty($this->layoutView)) {
            parent::run();
            return;
        }

        // check for results
        if ($this->dataProvider->getCount() > 0 || $this->showOnEmpty) {

            // get content from closure or view
            $content = ($this->layoutView instanceof Closure)
                ? call_user_func($this->layoutView)
                : $this->getView()->render($this->layoutView, $this->layoutViewParams);

            // replace sections
            $sections = ['{summary}', '{items}', '{pager}', '{sorter}'];
            foreach ($sections as $section) {
                if (strpos($content, $section) !== false) {
                    $content = str_replace($section, $this->renderSection($section), $content);
                }
            }
        }
        // get empty content
        else {
            $content = $this->renderEmpty();
        }

        echo $content;

    }

    /**
     * @inheritdoc
     */
    public function renderEmpty() {

        // run normal parent implementation if view is not set
        if (empty($this->emptyView)) {
            return parent::renderEmpty();
        }

        // render closure/view
        return ($this->emptyView instanceof Closure)
            ? call_user_func($this->emptyView)
            : $this->getView()->render($this->emptyView, $this->emptyViewParams);
    }
}