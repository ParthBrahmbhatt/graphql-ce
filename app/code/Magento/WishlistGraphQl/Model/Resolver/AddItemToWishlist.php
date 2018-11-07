<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Catalog\Model\Product;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Wishlist\Model\Wishlist;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * @inheritdoc
 */
class AddItemToWishlist implements ResolverInterface
{
    /**
     * @var WishlistProviderInterface
     */
    private $wishlistProvider;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param WishlistProviderInterface $wishlistProvider
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['input']['skus'])) {
            throw new GraphQlInputException(__('at least one "sku" value should be specified'));
        }
        $wishlist = $this->wishlistProvider->getWishlist();
        $this->addMultipleProducts($args['input']['skus'], $wishlist);
        if (!$wishlist) {
            throw new GraphQlInputException(__('Cannot get Wish List even from Customer ID'));
        }
        return [
            'wishlist' => [
                'sharing_code' => $wishlist->getSharingCode(),
                'updated_at' => $wishlist->getUpdatedAt()
            ]
        ];
    }

    /**
     * @param array $skus
     * @param Wishlist $wishList
     * @return Wishlist
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addMultipleProducts($skus, Wishlist $wishList)
    {
        foreach ($skus as $sku) {
            /** @var Product $product */
            $product = $this->productRepository->get($sku);
            $wishList->addNewItem($product);
        }
        return $wishList;
    }
}
