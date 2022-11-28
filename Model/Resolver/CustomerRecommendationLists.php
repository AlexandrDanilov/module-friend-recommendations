<?php
declare(strict_types=1);

namespace SwiftOtter\FriendRecommendations\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use SwiftOtter\FriendRecommendations\Api\Data\RecommendationListInterface;
use SwiftOtter\FriendRecommendations\Api\RecommendationListRepositoryInterface;

class CustomerRecommendationLists implements ResolverInterface
{
    private GetCustomer $getCustomer;
    private RecommendationListRepositoryInterface $listRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param GetCustomer $getCustomer
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RecommendationListRepositoryInterface $listRepository
     */
    public function __construct(
        GetCustomer                           $getCustomer,
        SearchCriteriaBuilder                 $searchCriteriaBuilder,
        RecommendationListRepositoryInterface $listRepository
    ) {
        $this->getCustomer = $getCustomer;
        $this->listRepository = $listRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     * @param ContextInterface @context
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(
        Field       $field,
        $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        /** @var ContextInterface $context */
        if (!$context->getUserId()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $customer = $this->getCustomer->execute($context);

        $this->searchCriteriaBuilder->addFilter('email', $customer->getEmail());
        $lists = $this->listRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        $result = [];

        foreach ($lists as $list) {
            $result[] = $this->formatListData($list);
        }

        return $result;
    }

    /**
     * @param RecommendationListInterface $list
     * @return array
     */
    private function formatListData(RecommendationListInterface $list)
    {
        return [
            'id' => (int)$list->getId(),
            'friendName' => $list->getFriendName(),
            'title' => $list->getTitle(),
            'note' => $list->getNote()
        ];
    }
}
