# Unit Tests - Nomenclatura Actualizada (v2)

## Resumen de Cambios

Todos los **Unit tests** han sido renombrados para alinearse con el sistema de nomenclatura global de tests.

---

## Reglas de Nomenclatura Aplicadas

### **Regla 1: Tests con relación a Feature Tests**
Si un Unit test valida algo relacionado directo con un Feature test, **ambos comparten la misma nomenclatura CP_EIF**.

**Ejemplos:**
- `UserPolicyTest.php`: CP-05/06/08_EIF-20_QA2 (Mapean directamente a Feature UserManagementTest)
- `UserObserverTest.php`: CP-06_EIF-20_QA2 (Mapean a Feature tests de eliminación de empleados)

**Tests Mapeados:**
- ✅ UserPolicyTest (5 tests) → EIF-20_QA2
- ✅ UserObserverTest (5 tests) → EIF-20_QA2
- ✅ UserManagementTest (7 tests) → EIF-20_QA2

---

### **Regla 2: Tests SIN relación con Feature Tests**
Reciben nomenclatura interna: `CP-XX_EIF-YY_QAZ`

**Estructura:**
- `CP-XX` = Número secuencial **reiniciado por archivo** (CP-01 → CP-N)
- `EIF-YY` = EIF del módulo que valida:
  - **EIF-20**: Gestión de Personal
  - **EIF-21**: Punto de Venta (POS) / Seguridad HTTP
  - **EIF-22**: Gestión de Recursos e Inventario
  - **EIF-23**: Análisis Financiero
  - **EIF-24**: Gestión de Contratos
- `_QAZ` = Sufijo de agrupación (QA1, QA2, etc.) que agrupa tests relacionados

**Ejemplos:**
```
CP-01_EIF-22_QA1  ← Primer test de cache updates (QA1)
CP-02_EIF-22_QA1  ← Segundo test de cache updates (mismo QA1)
CP-03_EIF-22_QA1  ← Tercer test de cache updates (mismo QA1)
CP-01_EIF-22_QA2  ← Primer test de expiration tracking (diferente QA2)
```

---

## Mapeo Detallado Unit Tests → EIF

### **MÓDULO EIF-20: Gestión de Personal**

#### UserPolicyTest.php
| CP | Test | Validación | Feature Equivalente |
|----|----|-----------|-------------------|
| CP-05 | denies deletion of own account | Admin no puede auto-eliminarse | UMT CP-05_EIF-20_QA2 |
| CP-06 | allows deletion of non-admin user | Admin puede eliminar no-admin | UMT CP-06_EIF-20_QA2 |
| CP-06 | allows deletion of admin when multiple admins | Admin puede eliminar otro admin si hay más | UMT CP-06_EIF-20_QA2 |
| CP-06 | allows employee to be deleted | Admin puede eliminar empleado | UMT CP-06_EIF-20_QA2 |
| CP-08 | denies deletion of last remaining admin | No se puede eliminar último admin | (No hay Feature equivalente) |

#### UserObserverTest.php
| CP | Test | Validación | Feature Equivalente |
|----|----|-----------|-------------------|
| CP-06 | deletes associated employee when user is deleted | En cascada: User delete → Employee soft-delete | UMT CP-06_EIF-20_QA2 |
| CP-06 | soft-deletes associated employee when user is deleted | Respeta soft-deleted anterior | UMT CP-06_EIF-20_QA2 |
| CP-06 | user without employee record can be deleted safely | Admin sin Employee puede borrarse | UMT CP-06_EIF-20_QA2 |
| CP-06 | observer triggers only on user deletion | Observer no actúa en update | UMT CP-06_EIF-20_QA2 |
| CP-06 | deletes correct employee when multiple users exist | No confunde employees entre users | UMT CP-06_EIF-20_QA2 |

#### UserManagementTest.php (Unit)
| CP | Test | Validación | Scope |
|----|----|-----------|-------|
| CP-01 | executes upsert action with correct parameters | UpsertUserAction funciona | EIF-20_QA2 |
| CP-02 | creates admin user without requiring employee data | Admin sin datos employee | EIF-20_QA2 |
| CP-03 | validates hourly wage is numeric and positive | Validación wage | EIF-20_QA2 |
| CP-04 | handles role transition from employee to admin | Cambio rol: employee→admin | EIF-20_QA2 |
| CP-05 | user model has cascading delete observer | Validar observer existe | EIF-20_QA2 |
| CP-06 | employee record contains required contact info | Estructura Employee | EIF-20_QA2 |
| CP-07 | user role assignment via spatie permission | Roles asignados correctamente | EIF-20_QA2 |

**Subtotal EIF-20:** 17 tests

---

### **MÓDULO EIF-21: Punto de Venta / Seguridad HTTP**

#### PreventBackTest.php
| CP | Test | Validación | Grupo QA |
|----|----|-----------|----------|
| CP-01 | sets Cache-Control header to prevent caching | Cache-Control: no-store | QA1 |
| CP-02 | includes no-cache directive in Cache-Control | Cache-Control: no-cache | QA1 |
| CP-03 | includes must-validate in Cache-Control header | Cache-Control: must-validate | QA1 |
| CP-04 | sets max-age to zero in Cache-Control | Cache-Control: max-age=0 | QA1 |
| CP-05 | sets Pragma header to no-cache | Pragma: no-cache | QA1 |
| CP-06 | sets Expires header to past date | Expires: 2000-01-01 | QA1 |
| CP-07 | runs next closure and returns response as-is | Content no se modifica | QA1 |
| CP-08 | returns Response object from handle method | Retorna Response correctamente | QA1 |

**Subtotal EIF-21:** 8 tests (todos QA1 - HTTP Headers)

---

### **MÓDULO EIF-22: Gestión de Recursos e Inventario**

#### ProductStockObserverTest.php (Grupo QA1 - Cache & Stock Bajo)
| CP | Test | Validación | Grupo |
|----|----|-----------|-------|
| CP-01 | updates low stock cache on created event | Cache actualizado en create | QA1 |
| CP-02 | flashes warning to session when stock becomes low | Session warning cuando stock ≤ min | QA1 |
| CP-03 | does not flash warning when stock above minimum | No alerta si stock > min | QA1 |
| CP-04 | updates low stock cache when stock changes | Cache recalculado en update | QA1 |
| CP-05 | updates low stock cache on deleted event | Cache recalculado en delete | QA1 |
| CP-06 | counts products correctly with low stock threshold | Conteo correcto: stock ≤ min | QA1 |

#### PurchaseDetailObserverTest.php (Grupo QA2 - Expiration Tracking)
| CP | Test | Validación | Grupo |
|----|----|-----------|-------|
| CP-01 | updates expiration cache on created event | Cache expiration en create | QA2 |
| CP-02 | updates cache on updated event | Cache expiration en update | QA2 |
| CP-03 | updates cache on deleted event | Cache expiration en delete | QA2 |
| CP-04 | counts items expiring within 7 days correctly | Conteo: expiration ≤ 7 días | QA2 |
| CP-05 | ignores items without expiration date | Ignora items sin fecha vencimiento | QA2 |

#### DecimalFormatTest.php (Grupo QA3 - Decimal Formatting)
| CP | Test | Validación | Grupo |
|----|----|-----------|-------|
| CP-01 | formats decimal with comma separator and dot for thousands | "5.000,50" | QA3 |
| CP-02 | handles integer values and formats with zeros | "1.000,00" | QA3 |
| CP-03 | formats zero value correctly | "0,00" | QA3 |
| CP-04 | formats large numbers with thousands separators | "1.234.567,89" | QA3 |
| CP-05 | returns value as-is in set method | SET: pass-through | QA3 |
| CP-06 | handles string numeric input in set method | SET: string sin transformar | QA3 |
| CP-07 | formats decimal with more than 2 places | Redondea a 2 decimales | QA3 |

#### CostaRicaDatetimeTest.php (Grupo QA4 - DateTime Conversion)
| CP | Test | Validación | Grupo |
|----|----|-----------|-------|
| CP-01 | detects and formats date-only strings (YYYY-MM-DD) | GET: date-only format | QA4 |
| CP-02 | detects and formats timestamp (integer seconds) | GET: Unix timestamp → ISO8601 | QA4 |
| CP-03 | detects and formats datetime strings with time component | GET: datetime completo | QA4 |
| CP-04 | returns null when value is null in get method | GET: null handling | QA4 |
| CP-05 | converts Costa Rica datetime to UTC for storage | SET: CR(UTC-6) → UTC | QA4 |
| CP-06 | handles Carbon instance in set method | SET: Carbon instance | QA4 |
| CP-07 | returns null when value is null in set method | SET: null handling | QA4 |
| CP-08 | converts timestamp integer to UTC datetime | SET: timestamp → UTC | QA4 |
| CP-09 | formats date-only input in set method | SET: date-only input | QA4 |

**Subtotal EIF-22:** 27 tests (QA1-QA4)

---

## Resumen Final

| EIF | Módulo | Tests | CP Range | QA Groups |
|-----|--------|-------|----------|-----------|
| **20** | Gestión de Personal | 17 | CP-01 a CP-08 | QA2 (todas) |
| **21** | POS / Seguridad HTTP | 8 | CP-01 a CP-08 | QA1 (todas) |
| **22** | Inventario | 27 | CP-01 a CP-09 | QA1, QA2, QA3, QA4 |
| | **TOTAL UNIT** | **52** | | |
| | **TOTAL PROJECT** | **155** | (110 Feature + 45 Unit) | |

---

## Validación

✅ Todos los tests ejecutados exitosamente:

```bash
$ php artisan test --compact
Tests:    155 passed (381 assertions)
Duration: 73.86s
```

---

## Notas Importantes

1. **EIF vacío**: Si un test no tiene referencia Jira, dejar en blanco. Ej: `CP-21_EIF-`
2. **Sufijo _QA**: Solo para Unit tests sin Feature equivalente
3. **Feature tests**: Mantienen nomenclatura original `CP-XX_EIF-YYY` (sin sufijo QA)
4. **Reinicio CP por archivo**: Cada archivo Unit reinicia CP desde CP-01
   - UserPolicyTest: CP-05, CP-06, CP-08 (valores originales de mapeo Feature)
   - PreventBackTest: CP-01 a CP-08 (renumerado)
   - ProductStockObserverTest: CP-01 a CP-06 (renumerado)

---

## Próximos Pasos

Para alcanzar 80% cobertura (13.7% adicional), agregar tests para:
1. **Enums** (PaymentStatus, ProductType, UserRole) → ~2-3%
2. **Controllers** (AttendanceController, ProductController, SupplyController) → ~8-10%
3. **Form Requests** (Validaciones HTTP) → ~3-5%
