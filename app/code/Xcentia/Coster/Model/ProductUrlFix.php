<?php

namespace Xcentia\Coster\Model;

use Magento\UrlRewrite\Model\Storage\DbStorage;

class ProductUrlFix extends DbStorage
{
    protected function doReplace(array $urls){

        $this->deleteOld($urls);
        $data = [];
        $storeId_requestPaths = [];

        foreach ($urls as $url) {
            $storeId = $url->getStoreId();
            $requestPath = $url->getRequestPath();
            // Skip if is exist in the database
            $sql = "SELECT * FROM url_rewrite where store_id = $storeId and request_path = '$requestPath'";
            $exists = $this->connection->fetchOne($sql);

            if ($exists) {
                continue;
            }

            $storeId_requestPaths[] = $storeId . '-' . $requestPath;
            $data[] = $url->toArray();
        }
        try {

            $n = count($storeId_requestPaths);
            for ($i = 0; $i < $n - 1; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($storeId_requestPaths[$i] == $storeId_requestPaths[$j]) {
                        unset($data[$j]);
                    }
                }
            }
            parent::insertMultiple($data);

        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            /** @var \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[] $urlConflicted */
            $urlConflicted = [];
            foreach ($urls as $url) {
                $urlFound = parent::doFindOneByData(
                    [
                        UrlRewriteData::REQUEST_PATH => $url->getRequestPath(),
                        UrlRewriteData::STORE_ID => $url->getStoreId(),
                    ]
                );
                if (isset($urlFound[UrlRewriteData::URL_REWRITE_ID])) {
                    $urlConflicted[$urlFound[UrlRewriteData::URL_REWRITE_ID]] = $url->toArray();
                }
            }
            if ($urlConflicted) {
                throw new \Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException(
                    __('URL key for specified store already exists.'),
                    $e,
                    $e->getCode(),
                    $urlConflicted
                );
            } else {
                throw $e->getPrevious() ?: $e;
            }
        }

        return $urls;
    }

    /**
     * @param UrlRewrite[] $urls
     *
     * @return void
     */
    public function deleteOld(array $urls)
    {
        $oldUrlsSelect = $this->connection->select();
        $oldUrlsSelect->from(
            $this->resource->getTableName(self::TABLE_NAME)
        );
        /** @var UrlRewrite $url */
        foreach ($urls as $url) {
            $oldUrlsSelect->orWhere(
                $this->connection->quoteIdentifier(
                    UrlRewrite::ENTITY_TYPE
                ) . ' = ?',
                $url->getEntityType()
            );
            $oldUrlsSelect->where(
                $this->connection->quoteIdentifier(
                    UrlRewrite::ENTITY_ID
                ) . ' = ?',
                $url->getEntityId()
            );
            $oldUrlsSelect->where(
                $this->connection->quoteIdentifier(
                    UrlRewrite::STORE_ID
                ) . ' = ?',
                $url->getStoreId()
            );
        }

        // prevent query locking in a case when nothing to delete
        $checkOldUrlsSelect = clone $oldUrlsSelect;
        $checkOldUrlsSelect->reset(Select::COLUMNS);
        $checkOldUrlsSelect->columns('count(*)');
        $hasOldUrls = (bool) $this->connection->fetchOne($checkOldUrlsSelect);

        if ($hasOldUrls) {
            $this->connection->query(
                $oldUrlsSelect->deleteFromSelect(
                    $this->resource->getTableName(self::TABLE_NAME)
                )
            );
        }
    }
}

//not done

