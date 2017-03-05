<?php

interface iSite {
    public function get_sources_list();

    public function get_statement($id);

    public function get_statements($source_url); // optional filtering by source
}