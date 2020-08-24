<?php

namespace Smartbox\ApiBundle\HttpKernel\CacheWarmer;

use Smartbox\ApiBundle\Security\UserList\FileList;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

/**
 * Put user list on cache warmup.
 */
class UserFileListCacheWarmer extends CacheWarmer
{
    /**
     * @var FileList
     */
    private $list;

    /**
     * UserFileListCacheWarmer constructor.
     */
    public function __construct(FileList $list)
    {
        $this->list = $list;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->list->buildCache();
    }
}
