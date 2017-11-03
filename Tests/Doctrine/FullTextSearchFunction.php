<?php
/**
 * This file is part of the SpsComponent package.
 *
 * (c) Evgeniy Budanov <budanov.ua@gmail.comm> 2017.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Zk2\SpsComponent\QueryBuilderException;

/**
 * Class FullTextSearchFunction
 */
class FullTextSearchFunction extends FunctionNode
{
    /**
     * @var array|Node[]
     */
    public $fields = [];

    /**
     * @var Node
     */
    public $queryString;

    /**
     * @var Node
     */
    public $mode;

    /**
     * @var Node
     */
    public $not = 0;

    /**
     * @param Parser $parser
     *
     * @throws QueryBuilderException
     */
    public function parse(Parser $parser)
    {
        $platform = $parser->getEntityManager()->getConnection()->getDatabasePlatform()->getName();
        switch ($platform) {
            case 'postgresql':
                $parser->match(Lexer::T_IDENTIFIER);
                $parser->match(Lexer::T_OPEN_PARENTHESIS);
                $this->fields[] = $parser->StringPrimary();
                $parser->match(Lexer::T_COMMA);
                $this->queryString = $parser->StringPrimary();

                if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
                    $parser->match(Lexer::T_COMMA);
                    $this->mode = $parser->StringPrimary();
                }

                $parser->match(Lexer::T_CLOSE_PARENTHESIS);
                break;
            case 'mysql':
                $parser->match(Lexer::T_IDENTIFIER);
                $parser->match(Lexer::T_OPEN_PARENTHESIS);

                do {
                    $this->fields[] = $parser->StateFieldPathExpression();
                    $parser->match(Lexer::T_COMMA);
                } while ($parser->getLexer()->isNextToken(Lexer::T_IDENTIFIER));

                $this->queryString = $parser->InParameter();

                while ($parser->getLexer()->isNextToken(Lexer::T_STRING)) {
                    $this->mode = $parser->Literal();
                }

                if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
                    $parser->match(Lexer::T_COMMA);
                    $this->not = $parser->InParameter();
                }

                $parser->match(Lexer::T_CLOSE_PARENTHESIS);
                break;
            default:
                throw new QueryBuilderException(
                    sprintf('Platform "%s" does not supported full text search', $platform)
                );
        }
    }

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     *
     * @throws QueryBuilderException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform()->getName();
        switch ($platform) {
            case 'postgresql':
                if ($this->mode) {
                    $query =
                        $this->fields[0]->dispatch($sqlWalker).' @@ to_tsquery('.$this->mode->dispatch(
                            $sqlWalker
                        ).', '.$this->queryString->dispatch($sqlWalker).')';
                } else {
                    $query =
                        $this->fields[0]->dispatch($sqlWalker).' @@ to_tsquery('.$this->queryString->dispatch(
                            $sqlWalker
                        ).')';
                }

                return $query;
            case 'mysql':
                $haystack = array_map(
                    function (Node $field) use ($sqlWalker) {
                        return $field->dispatch($sqlWalker);
                    },
                    $this->fields
                );
                $haystack = implode(',', $haystack);
                $query =
                    ($this->not ? 'NOT ' : '').'MATCH('.$haystack.') AGAINST ('.$this->queryString->dispatch(
                        $sqlWalker
                    ).($this->mode ? ' '.$this->mode->dispatch($sqlWalker) : '').')';

                return $query;
            default:
                throw new QueryBuilderException(
                    sprintf('Platform "%s" does not supported full text search', $platform)
                );
        }
    }
}
