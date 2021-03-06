<?php
namespace GithubConnector;

use ShowMeTheIssue\Repo\RepoInterface;
use ShowMeTheIssue\Entity\Issue;
use Zend\ServiceManager\ServiceLocatorInterface;
use ShowMeTheIssue\Collection\IssueCollection;

/**
 * Connects with Github
 *
 * @author diego
 *
 */
class GithubService implements RepoInterface
{
    use\Zend\ServiceManager\ServiceLocatorAwareTrait;

    protected $config;

    /**
     *
     * @var \Github\HttpClient\CachedHttpClient
     */
    protected $client;

    /**
     *
     * @param  array                   $config
     * @param  ServiceLocatorInterface $serviceLocator $serviceLocator
     * @throws \Exception
     * @todo Handle exceptions as it should!!! GO FOR IT!
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $client = new \Github\HttpClient\CachedHttpClient();
        $client->setCache(new \Github\HttpClient\Cache\FilesystemCache('./data/cache/github-api-cache'));

        if (! empty($config['user_connect']['username']) && ! empty($config['user_connect']['password'])) {
            $client->authenticate($config['user_connect']['username'],
                                $config['user_connect']['password'],
                                \Github\Client::AUTH_HTTP_PASSWORD);
        } elseif (! empty($config['oauth']['oauth_consumer_key']) && 
                  ! empty($config['oauth']['oauth_consumer_secret'])) {
            echo "no oauth";
            throw new \Exception('Oauth Not supported yet');
        } else {
            throw new \Exception('No configuration provided for Github connector');
        }
        try {
            $this->client = new \Github\Client($client);
        } catch (\Github\Exception\InvalidArgumentException $e) {
            throw $e;
            //die("Argumentos invalidos para la conexion");
        }

    }

    /**
     * (non-PHPdoc)
     *
     * @see \ShowMeTheIssue\src\ShowMeTheIssue\Repo\RepoInterface::getIssues()
     * @return ShowMeTheIssue\Entity\Issue[]
     * @todo inject hydrator
     * @todo inject IssueService
     */
    public function getIssuesFromRepo($account = null, $repo = null, array $filter = [])
    {
        if ($repo == null) {
            throw new \InvalidArgumentException('No repo parameter specified.');
        }

        if ($account == null) {
            throw new \InvalidArgumentException('No account parameter specified for this repo: '.$repo);
        }

        $issues = $this->client->api('issue')->all($account, $repo, $filter);
        $issueList = new IssueCollection();
        $issueHydrator = new IssueHydrator();
        $i = 0;
        foreach ($issues as $issue) {
            $issueObject = new Issue();
            $issueHydrator->hydrate($issue, $issueObject);
            $issueList->append($issueObject);
        }
        return $issueList;
    }
}
