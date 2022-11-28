<?php
declare(strict_types=1);

namespace SwiftOtter\FriendRecommendations\Model\Resolver;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use SwiftOtter\FriendRecommendations\Api\Data\RecommendationListInterfaceFactory;
use SwiftOtter\FriendRecommendations\Api\Data\RecommendationListProductInterfaceFactory;
use SwiftOtter\FriendRecommendations\Api\RecommendationListRepositoryInterface;
use SwiftOtter\FriendRecommendations\Model\ResourceModel\RecommendationListProduct as RecommendationListProductResource;

class CreateRecommendationList implements ResolverInterface
{
    private $requiredParams = ['email', 'friendName', 'productSkus'];

    private RecommendationListInterfaceFactory $listFactory;
    private RecommendationListRepositoryInterface $listRepository;
    private RecommendationListProductInterfaceFactory $listProductFactory;
    private RecommendationListProductResource $listProductResource;

    /**
     * @param RecommendationListInterfaceFactory $listFactory
     * @param RecommendationListRepositoryInterface $listRepository
     * @param RecommendationListProductInterfaceFactory $listProductFactory
     * @param RecommendationListProductResource $listProductResource
     */
    public function __construct(
        RecommendationListInterfaceFactory        $listFactory,
        RecommendationListRepositoryInterface     $listRepository,
        RecommendationListProductInterfaceFactory $listProductFactory,
        RecommendationListProductResource         $listProductResource
    ) {
        $this->listFactory = $listFactory;
        $this->listRepository = $listRepository;
        $this->listProductFactory = $listProductFactory;
        $this->listProductResource = $listProductResource;
    }

    /**
     * {@inheritdoc}
     * @param ContextInterface $context
     * @throws GraphQlInputException
     * @throws CouldNotSaveException
     */
    public function resolve(
        Field       $field,
        $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        $this->validateInput($args);

        $title = $args['title'] ?? null;
        $note = $args['note'] ?? null;

        $list = $this->listFactory->create();
        $list->setEmail($args['email'])
            ->setFriendName($args['friendName'])
            ->setTitle($title)
            ->setNote($note);

        $list = $this->listRepository->save($list);

        $this->saveProductsToList((int)$list->getId(), $args['productSkus']);

        return [
            'email' => $list->getEmail(),
            'friendName' => $list->getFriendName(),
            'title' => $list->getTitle(),
            'note' => $list->getNote()
        ];
    }

    /**
     * @param int $listId
     * @param array $skus
     * @return void
     * @throws AlreadyExistsException
     */
    private function saveProductsToList(int $listId, array $skus)
    {
        foreach ($skus as $sku) {
            $item = $this->listProductFactory->create();
            $item->setListId($listId)
                ->setSku($sku);
            $this->listProductResource->save($item);
        }
    }

    /**
     * @param $args
     * @return void
     * @throws GraphQlInputException
     */
    private function validateInput($args)
    {
        foreach ($this->requiredParams as $param) {
            if (empty($args[$param])) {
                throw new GraphQlInputException(__('Required parameter "' . $param . '" is missing'));
            }
        }
    }
}
