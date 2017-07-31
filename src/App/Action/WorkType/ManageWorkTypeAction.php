<?php

namespace App\Action\WorkType;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\Db\Adapter\Adapter;
use Zend\Paginator\Paginator;

class ManageWorkTypeAction
{
    private $router;

    private $template;

    private $adapter;

    public function __construct(Router\RouterInterface $router, Template\TemplateRendererInterface $template = null, Adapter $adapter)
    {
        $this->router = $router;
        $this->template = $template;
        $this->adapter = $adapter;
    }

    protected function doAdd($post)
    {
        if ($post['submitt'] == 'Save') {
            $table = new \App\Db\Table\WorkType($this->adapter);
            $table->insertRecords($post['new_worktype']);
        }
    }
    
    protected function doEdit($post)
    {
        if ($post['submitt'] == 'Save') {
            if (!is_null($post['id'])) {
                $table = new \App\Db\Table\WorkType($this->adapter);
                $table->updateRecord($post['id'], $post['edit_worktype']);
            }
        }
    }
    
    protected function doDelete($post)
    {
        if ($post['submitt'] == 'Delete') {
            if (!is_null($post['id'])) {
                $table = new \App\Db\Table\Work($this->adapter);
                $table->updateWorkTypeId($post['id']);
                $table = new \App\Db\Table\WorkType_WorkAttribute($this->adapter);
                $table->deleteRecordByWorkType($post['id']);
                $table = new \App\Db\Table\WorkType($this->adapter);
                $table->deleteRecord($post['id']);
            }
        }
    }
    
    protected function removeAttribute($post)
    {
        $attrs_to_remove = [];
        preg_match_all('/,?id_\d+/', $post['remove_attr'], $matches);
        foreach ($matches[0] as $id) :
                            $attrs_to_remove[] = (int) preg_replace("/^,?\w{2,3}_/", '', $id);
        endforeach;
        if (!is_null($attrs_to_remove)) {
            if (count($attrs_to_remove) != 0) {
                //remove attributes from a work type
                                $table = new \App\Db\Table\WorkType_WorkAttribute($this->adapter);
                $table->deleteAttributeFromWorkType($post['id'], $attrs_to_remove);
            }
        }
    }
    
    protected function addAttribute($post)
    {
        $attrs_to_add = [];
        preg_match_all('/,?nid_\d+/', $post['sort_order'], $matches);
        foreach ($matches[0] as $id) :
                            $attrs_to_add[] = (int) preg_replace("/^,?\w{2,3}_/", '', $id);
        endforeach;
        if (!is_null($attrs_to_add)) {
            if (count($attrs_to_add) != 0) {
                //Add attributes to work type
                $table = new \App\Db\Table\WorkType_WorkAttribute($this->adapter);
                $table->addAttributeToWorkType($post['id'], $attrs_to_add);
            }
        }
    }
    protected function doAttributeSort($post)
    {
        if ($post['submitt'] == 'Save') {
            if (!empty($post['remove_attr'])) {
                $this->removeAttribute($post);
            }
            if (!empty($post['sort_order'])) {
                $this->addAttribute($post);
            }
                    //after adding attrs to work type, adjust ranks
                    $table = new \App\Db\Table\WorkType_WorkAttribute($this->adapter);
            $table->updateWorkTypeAttributeRank($post['id'], $post['sort_order']);
        }
    }
    
    protected function doAction($post)
    {
        //add a new work type
        if ($post['action'] == 'new') {
            $this->doAdd($post);
        }
        //edit a work type
        if ($post['action'] == 'edit') {
            $this->doEdit($post);
        }
        //delete a work type
        if ($post['action'] == 'delete') {
            $this->doDelete($post);
        }
        //add, remove attributes to work type
        if ($post['action'] == 'sortable') {
            $this->doAttributeSort($post);
        }
    }
    
    protected function getPaginator($post)
    {
        //add, edit, delete actions on worktype
        if (!empty($post['action'])) {
            //add edit delete worktypes and manage attributes
            $this->doAction($post);
                        
            //Cancel add\edit\delete
            if ($post['submitt'] == 'Cancel') {
                $table = new \App\Db\Table\WorkType($this->adapter);

                return new Paginator(new \Zend\Paginator\Adapter\DbTableGateway($table));
            }
        }
        // default: blank for listing in manage
        $table = new \App\Db\Table\WorkType($this->adapter);

        return new Paginator(new \Zend\Paginator\Adapter\DbTableGateway($table));
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $query = $request->getqueryParams();
        $post = [];
        if ($request->getMethod() == 'POST') {
            $post = $request->getParsedBody();
        }
        $paginator = $this->getPaginator($post);
        $paginator->setDefaultItemCountPerPage(7);
        //$allItems = $paginator->getTotalItemCount();
        $countPages = $paginator->count();

        $currentPage = isset($query['page']) ? $query['page'] : 1;
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $paginator->setCurrentPageNumber($currentPage);

        if ($currentPage == $countPages) {
            $next = $currentPage;
            $previous = $currentPage - 1;
        } elseif ($currentPage == 1) {
            $next = $currentPage + 1;
            $previous = 1;
        } else {
            $next = $currentPage + 1;
            $previous = $currentPage - 1;
        }

        $searchParams = [];

        if (isset($post['action']) && $post['action'] == 'sortable' && $post['submitt'] == 'Save') {
            //if ($post['action'] == 'sortable' && $post['submitt'] == 'Save') {
                return new HtmlResponse(
            $this->template->render(
                'app::worktype::manage_worktypeattribute',
                [
                    'rows' => $paginator,
                    'previous' => $previous,
                    'next' => $next,
                    'countp' => $countPages,
                    'request' => $request,
                    'adapter' => $this->adapter,
                     'searchParams' => implode('&', $searchParams),
                ]
            )
        );
            //}
        } else {
            return new HtmlResponse(
            $this->template->render(
                'app::worktype::manage_worktype',
                [
                    'rows' => $paginator,
                    'previous' => $previous,
                    'next' => $next,
                    'countp' => $countPages,
                    'searchParams' => implode('&', $searchParams),
                ]
            )
        );
        }
    }
}
