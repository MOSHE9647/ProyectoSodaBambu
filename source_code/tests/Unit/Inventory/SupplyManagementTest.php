<?php

use App\Models\Supply;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-01_EIF-49 - supply model is soft deletable', function () {
    // Given: a persisted supply.
    $supply = Supply::factory()->create();

    // When: deleting the supply.
    $supply->delete();

    // Then: the record is removed from active query scope and available in trash.
    expect(Supply::find($supply->id))->toBeNull();
    expect(Supply::onlyTrashed()->find($supply->id))->not->toBeNull();
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-02_EIF-49 - supply can be restored from soft delete', function () {
    // Given: a soft-deleted supply.
    $supply = Supply::factory()->create([
        'name' => 'Arroz',
        'measure_unit' => 'kg',
    ]);
    $supply->delete();

    // When: restoring the supply.
    $supply->restore();

    // Then: the supply becomes active again with deleted_at set to null.
    $restoredSupply = Supply::find($supply->id);

    expect($restoredSupply)->not->toBeNull()
        ->and($restoredSupply->deleted_at)->toBeNull();
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-03_EIF-49 - supply purchaseDetails relation is a morph many relation', function () {
    // Given: a supply model instance.
    $supply = new Supply;

    // When: requesting the purchaseDetails relationship object.
    $relation = $supply->purchaseDetails();

    // Then: the model defines the expected morph-many relation.
    expect($relation)->toBeInstanceOf(MorphMany::class);
});
