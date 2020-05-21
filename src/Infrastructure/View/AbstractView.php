<?php

namespace Osds\DDDCommon\Infrastructure\View;

abstract class AbstractView implements ViewInterface
{

    protected $variables = [];
    private $template;

    public function setVariables($variables)
    {
        foreach($variables as $key => $value) {
            $this->setVariable($key, $value);
        }
    }
    
    public function getVariables()
    {
        return $this->variables;
    }

    public function setVariable($key, $value)
    {
        $this->variables[$key] = $value;
    }
    
    public function getVariable($key)
    {
        if(isset($this->variables[$key])) {
            return $this->variables[$key];
        } else {
            return null;
        }
    }

    public function createTemplate($templatePath, $templateName)
    {
        return file_get_contents($templatePath);
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }
    
    public function getTemplate()
    {
        return $this->template;
    }

    public function render($return = false)
    {
        echo $this->template;
        echo "<pre>";
        var_dump($this->variables);
        exit;
    }

    /**
     * @param $total => total number of elements
     * @param array $configuration
     *                      display_mode: results, pages
     *                      pages_per_page: if display_mode = pages, pages_per_page will display N pages in the navigator
     *                      items_per_page: number of items in a page
     */
    public function generatePagination($total, $configuration = [])
    {
        if(!isset($configuration['display_mode'])) $configuration['display_mode'] = 'results';
        if(!isset($configuration['items_per_page'])) $configuration['items_per_page'] = '20';
        
        if (isset($configuration['display_mode'])) {
            $vars['mode'] = $configuration['display_mode'];
        } else {
            $vars['mode'] = 'pages';
        }

        if (isset($configuration['items_per_page'])) {
            $items_per_page = $configuration['items_per_page'];
        } else {
            $items_per_page = 10;
        }

        if (isset($configuration['pages_per_page'])) {
            $pages_per_page = $configuration['pages_per_page'];
        } else {
            $pages_per_page = 10;
        }

        #total number of pages
        $num_pages = ceil($total / $items_per_page);

        #just one, no need of pagination
        if ($num_pages <= 1) {
            return ['paginator' => '', 'items_per_page' => $items_per_page];
        }

        #generate parameter for the url
        preg_match('/query_filters\[page\]=(.*)\/?/i', $_SERVER['REQUEST_URI'], $page_num);
        if (isset($page_num[1])) {
            $current_page = $page_num[1];
            $href = str_replace('query_filters[page]='.$current_page, 'query_filters[page]=%page%', $_SERVER['REQUEST_URI'] );
        } else {
            $current_page = 1;
            $href = $_SERVER['REQUEST_URI'];
            if (strstr($_SERVER['REQUEST_URI'], '?')) {
                $href .= '&';
            } else {
                $href .= '?';
            }
            $href .= 'query_filters[page]=%page%';
        }

        #first page to display in the paging navigator
        $first_page = max(1, $current_page - floor($pages_per_page / 2));
        #last page to display in the paging navigator
        $last_page = min($num_pages, $first_page + $pages_per_page - 1);

        #first page to display on the paging navigator is not the first page => display link to first page
        if ($first_page != 1) {
            $first_page_link = str_replace('%page%', 1, $href);
            $vars['first'] = $first_page_link;
        }

        #we are not on the first page => we need a link to go to the previous page
        if($current_page != 1) {
            $prev_page_link = str_replace('%page%', $current_page - 1, $href);
            $vars['previous'] = $prev_page_link;
        }

        #links to pages
        for ($i=$first_page;$i<=$last_page;$i++) {
            if($i == $current_page) {
                $vars['current_page'] = $i;
            }
            $page_link = str_replace('%page%', $i, $href);
            $vars['pages'][$i] = $page_link;
        }

        #current page is not the last => display a link to the next page
        if($current_page < $num_pages) {
            $next_page_link = str_replace('%page%', $current_page + 1, $href);
            $vars['next'] = $next_page_link;
        }

        #last page to display on the paging navigator is not the last page => display a link to the last page
        if($last_page != $num_pages) {
            $last_page_link = str_replace('%page%', $num_pages, $href);
            $vars['last'] = $last_page_link;
        }

        #paging navigator itself
        return [
            'paginator' => $vars,
            'items_per_page' => $items_per_page
        ];

    }

}