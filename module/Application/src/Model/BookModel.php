<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;

class BookModel
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getAllBooks()
    {
        return $this->tableGateway->select();
    }

    public function getAvailableBooks(): array
    {
        $books = $this->getAllBooks();
        $availableBooks = [];

        foreach ($books as $book) {
            if ($book->quantity > 0) {
                $availableBooks[] = $book;
            }
        }

        return $availableBooks;
    }

    public function getBookById($id)
    {
        $rowset = $this->tableGateway->select(['id' => (int) $id]);
        return $rowset->current();
    }

    public function addBook($data)
    {
        $data = [
            'title' => $data['title'],
            'author' => $data['author'],
            'quantity' => (int) $data['quantity'],
        ];
        $this->tableGateway->insert($data);
    }

    public function updateBook($id, $data)
    {
        $data = [
            'title' => $data['title'],
            'author' => $data['author'],
            'quantity' => (int) $data['quantity'],
        ];
        $this->tableGateway->update($data, ['id' => (int) $id]);
    }

    public function deleteBook($id)
    {
        $this->tableGateway->delete(['id' => (int) $id]);
    }

    public function updateBookQuantity($id, $quantity)
    {
        $this->tableGateway->update(['quantity' => (int) $quantity], ['id' => (int) $id]);
    }

    public function getLowStockBooks($threshold = 5)
    {
        return $this->tableGateway->select(function (Select $select) use ($threshold) {
            $select->where->lessThanOrEqualTo('quantity', $threshold);
            $select->where->greaterThanOrEqualTo('quantity', 0);
        });
    }

    public function searchBooks($searchTerm)
    {
        $searchTerm = trim($searchTerm);
        if (empty($searchTerm)) {
            return [];
        }

        return $this->tableGateway->select(function (Select $select) use ($searchTerm) {
            $select->where->like('title', '%' . $searchTerm . '%')
                   ->or->like('author', '%' . $searchTerm . '%');
        });
    }
}