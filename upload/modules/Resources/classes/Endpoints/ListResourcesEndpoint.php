<?php

class ListResourcesEndpoint extends NoAuthEndpoint {

    public function __construct() {
        $this->_route = 'resources';
        $this->_module = 'Resources';
        $this->_description = 'List all resources';
        $this->_method = 'GET';
    }

    public function execute(Nameless2API $api): void {
        $query = 'SELECT * FROM nl2_resources';
        $where = ' WHERE id <> 0';
        $limit = '';
        $params = [];

        if (isset($_GET['id'])) {
            $where .= ' AND id = ?';
            array_push($params, $_GET['id']);
        }

        if (isset($_GET['creator'])) {
            $where .= ' AND creator_id = ?';
            array_push($params, $_GET['creator']);
        }

        if (isset($_GET['category'])) {
            $where .= ' AND category_id = ?';
            array_push($params, $_GET['category']);
        }

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $limit .= ' LIMIT '. $_GET['limit'];
        }

        $resources_list = [];
        $resources_query = $api->getDb()->query($query . $where . $limit, $params)->results();
        foreach ($resources_query as $resource) {
            $author = new User($resource->creator_id);
            
            $resources_list[] = [
                'id' => $resource->id,
                'name' => $resource->name,
                'description' => $resource->description,
                'author' => [
                    'id' => $resource->id,
                    'username' => $author->exists() ? $author->getDisplayname(true) : $api->getLanguage()->get('general', 'deleted_user'),
                ],
                'contributors' => $resource->contributors,
                'created' => $resource->created,
                'updated' => $resource->updated,
                'rating' => $resource->rating,
                'latest_version' => $resource->latest_version,
                'price' => $resource->price,
                'views' => $resource->views,
                'downloads' => $resource->downloads,
                'url' => URL::getSelfURL() . ltrim(URL::build('/resources/resource/' . $resource->id . '-' . URL::urlSafe($resource->name))),
            ];
        }
        
        $api->returnArray(['resources' => $resources_list]);
    }
}