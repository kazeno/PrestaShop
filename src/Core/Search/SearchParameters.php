<?php
/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Core\Search;

use PrestaShopBundle\Entity\Repository\AdminFilterRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Retrieve filters parameters if any from the User request.
 */
final class SearchParameters implements SearchParametersInterface
{
    /**
     * @var AdminFilterRepository
     */
    private $adminFilterRepository;

    public function __construct(AdminFilterRepository $adminFilterRepository)
    {
        $this->adminFilterRepository = $adminFilterRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getFiltersFromRequest(Request $request, $filterClass)
    {
        $filters = new $filterClass();

        $queryParams = $request->query->all();
        $requestParams = $request->request->all();

        //If filters have a grid id then parameters are sent in a namespace (eg: grid_id[limit]=10 instead of limit=10)
        if (!empty($filters->getGridId())) {
            $queryParams = isset($queryParams[$filters->getGridId()]) ? $queryParams[$filters->getGridId()] : [];
            $requestParams = isset($requestParams[$filters->getGridId()]) ? $requestParams[$filters->getGridId()] : [];
        }

        $parameters = [];
        foreach (self::FILTER_TYPES as $type) {
            if (isset($queryParams[$type])) {
                $parameters[$type] = $queryParams[$type];
            } elseif (isset($requestParams[$type])) {
                $parameters[$type] = $requestParams[$type];
            }
        }
        $filters->replace($parameters);

        return $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFiltersFromRepository($employeeId, $shopId, $controller, $action, $filterClass)
    {
        $adminFilter = $this->adminFilterRepository->findByEmployeeAndRouteParams(
            $employeeId,
            $shopId,
            $controller,
            $action
        );

        if (null === $adminFilter) {
            return new $filterClass();
        }

        return new $filterClass(json_decode($adminFilter->getFilter(), true), $adminFilter->getUniqueKey());
    }

    /**
     * {@inheritdoc}
     */
    public function getFiltersFromRepositoryByUniqueKey($employeeId, $shopId, $uniqueKey, $filterClass)
    {
        $adminFilter = $this->adminFilterRepository->findOneBy([
            'employee' => $employeeId,
            'shop' => $shopId,
            'uniqueKey' => $uniqueKey,
        ]);

        if (null === $adminFilter) {
            return new $filterClass();
        }

        return new $filterClass(json_decode($adminFilter->getFilter(), true), $adminFilter->getUniqueKey());
    }
}
