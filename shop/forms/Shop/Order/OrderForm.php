<?php

namespace shop\forms\Shop\Order;

use shop\forms\CompositeForm;

/**
 * @property DeliveryForm $delivery
 * @property CustomerForm $customer
 */
class OrderForm extends CompositeForm
{
    // заметка от пользователя
    public $note;

    public function __construct(int $weight, array $config = [])
    {
        // записываем данные о заказе, передаем вес
        $this->delivery = new DeliveryForm($weight);
        // записываем данные о заказчике
        $this->customer = new CustomerForm();
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['note'], 'string'],
        ];
    }

    protected function internalForms(): array
    {
        return ['delivery', 'customer'];
    }
}