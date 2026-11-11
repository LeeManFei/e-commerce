<?php

class SimplePager
{
    public $limit;      // Page size
    public $page;       // Current page
    public $item_count; // Total item count
    public $page_count; // Total page count
    public $result;     // Result set (array of records)
    public $count;      // Item count on the current page

    public function __construct($query, $params, $limit, $page)
    {
        global $conn;

        if (!is_array($params)) {
            $params = [];
        }

        // Set limit & page
        $this->limit = ctype_digit($limit) ? max($limit, 1) : 10;
        $this->page = ctype_digit($page) ? max($page, 1) : 1;

        // --- FIXED COUNT QUERY (no regex) ---
        $countQuery = "SELECT COUNT(*) FROM ($query) AS total_rows";
        $stm = $conn->prepare($countQuery);
        $stm->execute($params);
        $this->item_count = (int) $stm->fetchColumn();

        $this->page_count = ceil($this->item_count / $this->limit);
        $offset = ($this->page - 1) * $this->limit;

        // Main query
        $stm = $conn->prepare($query . " LIMIT $offset, $this->limit");
        $stm->execute($params);
        $this->result = $stm->fetchAll();

        $this->count = count($this->result);
    }

    public function html($href = '', $attr = '')
    {
        if ($this->page_count <= 1)
            return;

        $prev = $this->page - 1;
        $next = $this->page + 1;

        echo "<nav class='pager' $attr>";

        // First & Previous
        if ($this->page > 1) {
            echo "<a href='?page=1&$href' class='pager-btn'>First</a>";
            echo "<a href='?page=$prev&$href' class='pager-btn'>Previous</a>";
        }


        // Smart page range
        $range = 5; // how many pages to show around current
        $start = max(1, $this->page - floor($range / 2));
        $end = min($this->page_count, $start + $range - 1);

        // Adjust start if we’re at the end
        if ($end - $start + 1 < $range) {
            $start = max(1, $end - $range + 1);
        }

        // Page links
        for ($p = $start; $p <= $end; $p++) {
            $active = $p == $this->page ? 'active' : '';
            echo "<a href='?page=$p&$href' class='pager-btn $active'>$p</a>";
        }

        // Next & Last
        if ($this->page < $this->page_count) {
            echo "<a href='?page=$next&$href' class='pager-btn'>Next</a>";
            echo "<a href='?page=$this->page_count&$href' class='pager-btn'>Last</a>";
        }


        echo "</nav>";
    }

}