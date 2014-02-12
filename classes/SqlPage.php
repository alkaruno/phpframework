<?php

class SqlPage
{
    private $content;

    private $currentPage;
    private $totalPages;

    function __construct($sql, $values = array(), $page = 1, $itemsOnPage = 10)
    {
        $this->currentPage = intval(preg_replace('/\D+/', '', $page));

        if (!is_array($values)) {
            $values = array($values);
        }

        $this->content = Db::getRows(str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $sql) . ' LIMIT ' . ($itemsOnPage * ($this->currentPage - 1)) . ', ' . $itemsOnPage, $values);
        $this->totalPages = ceil(Db::getValue('SELECT FOUND_ROWS()') / $itemsOnPage);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function getTotalPages()
    {
        return $this->totalPages;
    }

    public function getLinks($uri)
    {
        App::showView('pagination.tpl', array('uri' => $uri, 'current_page' => $this->currentPage, 'pages_count' => $this->totalPages));
    }
}