<?php
namespace GithubConnector;

use ShowMeTheIssue\Repo\RepoInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use BitbucketConnector\IssueHydrator;
use ShowMeTheIssue\Entity\IssueAbstract;

/**
 * Connects with Github
 * 
 * @author diego
 *        
 */
class GithubService implements RepoInterface, ServiceLocatorAwareInterface
{
    use\Zend\ServiceManager\ServiceLocatorAwareTrait;

    protected $config;

    protected $client;
    
    public function __construct(array $config)
    {
        $client = new \Github\HttpClient\CachedHttpClient();
        $client->setCache(new \Github\HttpClient\Cache\FilesystemCache('./data/cache/github-api-cache'));
        
        if (! empty($config['user_connect']['username']) && ! empty($config['user_connect']['password'])) {
            $client->authenticate($config['user_connect']['username'], $config['user_connect']['password'], \Github\Client::AUTH_HTTP_PASSWORD);
        } elseif (! empty($config['oauth']['oauth_consumer_key']) && ! empty($config['oauth']['oauth_consumer_secret'])) {
            echo "no oauth";
            throw new \Exception('Oauth Not supported yet');
        } else {
            throw new \Exception('No configuration provided for Github connector');
        }
        try {
            $this->client = new \Github\Client($client);
            $this->getIssuesFromRepo();
        } catch (\Github\Exception\InvalidArgumentException $e) {
            die("Argumentos invalidos para la conexion");
        }
    }

    /**
     * (non-PHPdoc)
     * 
     * @see \ShowMeTheIssue\src\ShowMeTheIssue\Repo\RepoInterface::getIssues() return ShowMeTheIssue\Entity\Issue[]
     */
    public function getIssuesFromRepo($repo = 'bim-analytics-backend', array $filter = ['state' => 'open'])
    {
        $issues = $this->client->api('issue')->all('caseinc', $repo, $filter);
        $issueList = [];
        $issueHydrator = new IssueHydrator();
        foreach ($issues as $issue) {
            $issueObject = new IssueAbstract();
            $issueHydrator->hydrate($issue, $issueObject);
            $issueList[] = $issueObject;
        }
        return $issueList;
    }
}