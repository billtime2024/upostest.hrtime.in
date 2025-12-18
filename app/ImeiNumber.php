<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\BusinessLocation;

class ImeiNumber extends Model
{
    const TYPE_IMEI = 'imei';
    const TYPE_SERIAL = 'serial';

    protected $fillable = ['purchase_item_id', 'type', 'identifier', 'is_sold', 'sell_line_id', 'transfer_sell_line_id', 'sold_at', 'return_transaction_id', 'returned_at', 'location_id', 'business_id'];

    public function purchaseLine()
    {
        return $this->belongsTo(PurchaseLine::class, 'purchase_item_id');
    }

    public function sellLine()
    {
        return $this->belongsTo(TransactionSellLine::class, 'sell_line_id');
    }

    public function transferSellLine()
    {
        return $this->belongsTo(TransactionSellLine::class, 'transfer_sell_line_id');
    }

    public function returnTransaction()
    {
        return $this->belongsTo(Transaction::class, 'return_transaction_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public function isSold()
    {
        return $this->is_sold;
    }

    public function markAsSold($sell_line_id)
    {
        $this->is_sold = true;
        $this->sell_line_id = $sell_line_id;
        $this->sold_at = now();
        $this->save();
    }

    public function markAsUnsold()
    {
        $this->is_sold = false;
        $this->sell_line_id = null;
        $this->sold_at = null;
        $this->save();
    }

    public function markAsReturned($return_transaction_id)
    {
        $this->is_sold = false;
        $this->return_transaction_id = $return_transaction_id;
        $this->returned_at = now();
        $this->save();
    }

    public function markAsNotReturned()
    {
        $this->return_transaction_id = null;
        $this->returned_at = null;
        $this->save();
    }

    public function isImei()
    {
        return $this->type === self::TYPE_IMEI;
    }

    public function isSerial()
    {
        return $this->type === self::TYPE_SERIAL;
    }

    public function transferToLocation($locationId)
    {
        $this->location_id = $locationId;
        $this->save();
    }
}