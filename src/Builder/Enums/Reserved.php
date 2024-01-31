<?php

declare(strict_types=1);

namespace Inspira\Database\Builder\Enums;

enum Reserved: string
{
	case SELECT = 'SELECT';
	case ALL = '*';
	case FROM = 'FROM';
	case WHERE = 'WHERE';
	case AND = 'AND';
	case OR = 'OR';
	case EXISTS = 'EXISTS';
	case INSERT_INTO = 'INSERT INTO';
	case VALUES = 'VALUES';
	case UPDATE = 'UPDATE';
	case SET = 'SET';
	case DELETE = 'DELETE';
	case LIKE = 'LIKE';
	case NOT_LIKE = 'NOT LIKE';
	case IS = 'IS';
	case IS_NOT = 'IS NOT';
	case BETWEEN = 'BETWEEN';
	case NOT_BETWEEN = 'NOT BETWEEN';
	case IN = 'IN';
	case NOT_IN = 'NOT IN';
	case AS = 'AS';
	case GROUP_BY = 'GROUP BY';
	case ORDER_BY = 'ORDER BY';
	case ASC = 'ASC';
	case DESC = 'DESC';
	case DISTINCT = 'DISTINCT';
	case HAVING = 'HAVING';
	case LIMIT = 'LIMIT';
	case OFFSET = 'OFFSET';
	case INNER_JOIN = 'INNER JOIN';
	case LEFT_JOIN = 'LEFT JOIN';
	case RIGHT_JOIN = 'RIGHT JOIN';
	case FULL_JOIN = 'FULL JOIN';
	case CROSS_JOIN = 'CROSS JOIN';
	case ON = 'ON';
	case UNION = 'UNION';
}
