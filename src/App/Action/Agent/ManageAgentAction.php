<?php

namespace App\Action\Agent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\Db\Adapter\Adapter;

class ManageAgentAction
{
    private $router;

    private $template;
    
    private $adapter;

    public function __construct(Router\RouterInterface $router, Template\TemplateRendererInterface $template = null, Adapter $adapter)
    {
        $this->router   = $router;
        $this->template = $template;
        $this->adapter  = $adapter;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $sth = $this->adapter->query("select * from agenttype");
        $rows = $sth->execute();

        return new HtmlResponse($this->template->render('app::agent::manage_agent', ['rows' => $rows]));
    }
     
     
}
