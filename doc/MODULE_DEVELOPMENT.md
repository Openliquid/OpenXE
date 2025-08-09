# Leitfaden zur Modulentwicklung für OpenXE

OpenXE ist ein PHP-basiertes Open-Source-ERP-System, das auf der Architektur von Xentral aufbaut und unter der AGPL-3.0-Lizenz steht. Dieses Dokument beschreibt die grundlegenden Konzepte und Best Practices zur Entwicklung eigener Module und Plugins.

## Kernarchitektur

* **PHP-Klassen (`www/lib`)**: Enthält wiederverwendbare Klassen und Helper. Module sollten hier ihre Geschäftslogik platzieren und dabei PSR-konforme Namensräume verwenden.
* **Seiten (`www/pages`)**: Jede Benutzeroberfläche besteht aus einer PHP-Seite, die als Controller fungiert. Sie bindet Klassen aus `www/lib` ein, verarbeitet Requests und bereitet Daten für das Template auf.
* **Datenbank**: OpenXE nutzt MariaDB und bindet die Datenbank über `PDO` ein. SQL-Abfragen sollten ausschließlich über vorbereitete Statements laufen, um SQL-Injection zu verhindern.
* **Smarty-Templates (`www/themes/<theme>/templates`)**: Die UI wird mit Smarty erstellt. Controller übergeben Daten an die Templates, die Präsentationslogik bleibt schlank und frei von direkter Geschäftslogik.

## Neues Modul erstellen

1. **Verzeichnisstruktur anlegen**
   ```text
   www/
   ├─ lib/MyModule.php          # Geschäftslogik
   ├─ pages/mymodule.php        # Controller
   └─ themes/new/templates/mymodule.tpl  # Smarty-Template
   ```

2. **Controller (`www/pages/mymodule.php`)**
   ```php
   <?php
   use Xentral\Modules\MyModule\MyModule;

   $module = new MyModule($app);
   $data = $module->getData();
   $app->Tpl->assign('DATA', $data);
   $app->Tpl->parse('PAGE', 'mymodule.tpl');
   $app->Tpl->output('PAGE');
   ```

3. **Geschäftslogik (`www/lib/MyModule.php`)**
   ```php
   <?php
   namespace Xentral\Modules\MyModule;

   class MyModule
   {
       private \PDO $db;

       public function __construct($app)
       {
           $this->db = $app->Container->get('Database');
       }

       public function getData(): array
       {
           $stmt = $this->db->prepare('SELECT id, name FROM example_table');
           $stmt->execute();
           return $stmt->fetchAll();
       }
   }
   ```

4. **Template (`www/themes/new/templates/mymodule.tpl`)**
   ```smarty
   {extends file="page.tpl"}
   {block name=content}
     <h1>Mein Modul</h1>
     <ul>
       {foreach from=$DATA item=row}
         <li>{$row.name}</li>
       {/foreach}
     </ul>
   {/block}
   ```

## Hooks und Events

OpenXE stellt eine Reihe von Events bereit, die Module über Hooks abonnieren können. Hooks werden in einer Klassenmethode registriert und vom System aufgerufen.

```php
<?php
class MyModule
{
    public function __construct($app)
    {
        $app->EventManager->attach('onSaveOrder', [$this, 'handleOrderSave']);
        $app->EventManager->attach('onCreateInvoice', [$this, 'handleInvoiceCreate']);
    }

    public function handleOrderSave(int $orderId, array $orderData): void
    {
        // Reagiere auf das Speichern eines Auftrags
    }

    public function handleInvoiceCreate(int $invoiceId, array $invoiceData): void
    {
        // Reagiere auf das Erstellen einer Rechnung
    }
}
```

### Häufige Hooks

| Hook            | Parameter                            | Beschreibung                                             |
|-----------------|--------------------------------------|----------------------------------------------------------|
| `onSaveOrder`   | `int $orderId, array $orderData`     | Ausgelöst nach dem Speichern eines Auftrags.             |
| `onCreateInvoice` | `int $invoiceId, array $invoiceData` | Ausgelöst nach dem Erstellen einer Rechnung.             |

## REST-API-Endpunkte

Für APIs existiert unter `www/api` eine REST-Struktur auf Basis von FastRoute. Ein neuer Endpunkt wird über einen Controller in `www/api/<modul>/index.php` registriert.

```php
<?php
use FastRoute\RouteCollector;

$dispatcher->addRoute('GET', '/api/mymodule', [MyModuleApi::class, 'get']);
```

Der Controller liefert JSON-Antworten und nutzt die gleichen PDO-basierten Datenbankklassen wie die Weboberfläche.

## Beispiel: Dashboard-Widget

1. **PHP-Klasse (`www/lib/Dashboard/MyWidget.php`)**
   ```php
   <?php
   namespace Xentral\Dashboard;

   class MyWidget
   {
       public static function register($app): void
       {
           $app->Widget->add('dashboard', 'my_widget.tpl', ['count' => self::getCount($app)]);
       }

       private static function getCount($app): int
       {
           $stmt = $app->Container->get('Database')->query('SELECT COUNT(*) FROM example_table');
           return (int)$stmt->fetchColumn();
       }
   }
   ```

2. **Template (`www/themes/new/templates/my_widget.tpl`)**
   ```smarty
   <div class="widget">
     <span>Anzahl: {$count}</span>
   </div>
   ```

3. **Hook in `www/pages/index.php`**
   ```php
   use Xentral\Dashboard\MyWidget;
   MyWidget::register($app);
   ```

Das Widget erscheint anschließend auf dem Dashboard.

## Best Practices

* **MVC-Muster**: Trennen Sie Datenzugriff, Geschäftslogik und Darstellung.
* **Sicherheit**: Verwenden Sie vorbereitete PDO-Statements, validieren Sie Benutzereingaben und setzen Sie CSRF-Tokens ein.
* **Coding-Standards**: Halten Sie sich an PSR-Standards und dokumentieren Sie Funktionen mit PHPDoc.
* **Internationalisierung**: Nutzen Sie Sprachdateien in `languages/`, um Module mehrsprachig zu gestalten.

## Lizenz und Community

OpenXE steht unter der **GNU Affero General Public License Version 3 (AGPL-3.0)**. Beiträge der Community sind ausdrücklich willkommen:

* Forken Sie das Repository und erstellen Sie Pull Requests.
* Diskutieren Sie Ideen im Community-Forum unter [https://openxe.org/community/](https://openxe.org/community/).
* Jede eingereichte Änderung muss kompatibel zur AGPL-3.0 sein.

---
*Dieses Dokument steht unter der AGPL-3.0-Lizenz und darf frei verteilt und verändert werden, solange alle Änderungen ebenfalls unter AGPL-3.0 veröffentlicht werden.*
