<?php

namespace amnah\yii2\widgets;

class LayoutListView extends \yii\widgets\ListView {

    /**
     * @var string The layout view file. This will take precedence over [[layout]]
     *
     * Similarly, the sections you can set in the view are:
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
     * @var string Empty view file
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

        // get content from view file
        if ($this->dataProvider->getCount() > 0 || $this->showOnEmpty) {
            $content = $this->getView()->render($this->layoutView, $this->layoutViewParams);

            // replace layout sections
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

        // render view with params
        return $this->getView()->render($this->emptyView, $this->emptyViewParams);
    }
}