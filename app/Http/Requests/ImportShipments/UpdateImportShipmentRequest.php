<?php

namespace App\Http\Requests\ImportShipments;

/**
 * Updates replace the whole shipment including its items, so the rules are
 * identical to creation.
 */
class UpdateImportShipmentRequest extends StoreImportShipmentRequest {}
