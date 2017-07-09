- [Overview](#overview)
- [Installation](#installation)
- [Grid View](#grid-view)
    - [Nested Grid View](#nested-grid-view)
- [Grid Filters](#grid-filters)
    - [Full Example](#grid-filters-full-example)
    - [Filter By Relation](#grid-filters-relations)
- [Columns](#columns)
    - [Data Column](#data-column)
    - [Counter Column](#counter-column)
    - [Action Column](#action-column)
- [Sort](#sort)
    - [Relation Sort](#relation-sort)
- [Pagination](#pagination)

<a name="overview"></a>
## Overview
Allows you to quickly display the data of any entity or array in the form of a 
table. This table already contains filtering, sorting and pagination.

So using 9 lines of code you can build something like this
![](http://i.imgur.com/SGvsTWa.png)

<a name="installation"></a>
## Installation
install the `GridBundle` executing this console command in your project:
```
$ composer require av/grid-bundle ~1.0
```
Update your `AppKernel.php`:
```php
$bundles = [
    new AV\GridBundle\AVGridBundle(),
];
```
GridBundle contains `js` and `css` and you need to include them manually.
To use code example below the [AsseticBundle](https://symfony.com/doc/current/assetic/asset_management.html) must be installed:

```php
{% block stylesheets %}
    {% stylesheets '@AVGridBundle/Resources/public/css/grid-view.css' %}
        <link rel="stylesheet" href="{{ asset_url }}" />
    {% endstylesheets %}
{% endblock %}

...

{% block javascripts %}
    {% javascripts '@AVGridBundle/Resources/public/js/grid-view.js' %}
        <script src="{{ asset_url }}"></script>
    {% endjavascripts %}
{% endblock %}
```
NOTE: `GridBundle` uses [bootstrap](http://getbootstrap.com/) framework and [jQuery](https://jquery.com/).

<a name="grid-view"></a>
## Grid View

`GridView` is the main component that allows you to create and manage you grid.
Each grid for each entity or array requires new instance of `GridView`.

Shortest way to create new grid:
```php
// IN YOUR CONTROLLER
$em = $this->getDoctrine()->getManager();

$dataSource = $this->get('av_grid.query_data_source')
    ->setEntityName(User::class)->setRootAlias('u')
    ->setDataSource($em->getRepository('User')->createQueryBuilder('u'));

$gridData = [
    'dataSource' => $dataSource,
];

return $this->render('index.html.twig', [
    'paginationInstance' => $dataSource->getPagination(),
    'gridView' => $this->get('av_grid.grid_view_factory')
        ->prepareGridView($gridData),
]);
```
And update your template:
```php
{{ gridView(gridView) }}
{{ gridPagination(paginationInstance) }}
```
DONE!

`GridViewBundle` provides a lot of options for configure your grid. Detailed 
explanation of sorting, filtering, pagination and columns options are described 
in corresponding sections of this documentation.

Extended example:
```php
$gridData = [
    'dataSource' => $dataSource,
    'filterEntity' => new User,
    'tableCaption' => 'Users',
    'emptyCell' => 'no data',
    'columns' => [
        [
            'label' => 'User ID',
            'attributeName' => 'id'
        ],
        [
            'label' => 'User Full Name',
            'content' => function($userEntity, $rowIndex) {
                return $userEntity->getFirstName().' '.$userEntity->getLastName(); 
            }
        ],
        [
            'attributeName' => 'created',
            'filterType' => DateType::class,
            'filterFieldOptions' => [
                'widget' => 'single_text'
            ]
        ]
    ]
];

return $this->render('index.html.twig', [
    'paginationInstance' => $dataSource->getPagination(),
    'gridView' => $this->get('av_grid.grid_view_factory')
        ->prepareGridView($gridData),
]);
```

`GridView` options:
Grid filter options:
- **`dataSource`** - Provides data for grid. Should implement `BaseDataSource` interface.
    - Type: `BaseDataSource`
    - Required: yes
- **`containerOptions`** - List of html attributes that will be applied to grid container.
    - Type: `array`
    - Required: no
    - Example: `['containerOptions' => ['data-type' => 'grid-container', 'class' => 'outer']]`
- **`tableOptions`** - List of html attributes that will be applied to grid table. Here you can override table css style.
    - Type: `array`
    - Required: no
    - Example: `['tableOptions' => ['class' => 'table table-hover']]`
- **`tableCaption`** - Grid table caption.
    - Type: `string`
    - Required: no
    - Example: `['tableCaption' => 'Table Caption']`
- **`showHeader`** - Whether the table header row will be shown.
    - Type: `bool`
    - Required: no
- **`headerRowOptions`** - List of html attributes that will be applied to grid table header row.
    - Type: `array`
    - Required: no
- **`rowOptions`** - Options for grid table rows.
    - Type: `array`
    - Required: no
- **`filterRowOptions`** - List of html attributes that will be applied row that contains filters.
    - Type: `array`
    - Required: no
- **`emptyCell`** - Value that will be used for empty table cell.
    - Type: `string`
    - Required: no
- **`filterEntity`** - Instance of target entity that will be used for creating filter fields.
    - Type: `object`
    - Required: no
- **`filterUrl`** - Target url which will accept filter data. Current route will be used by default.
    - Type: `string`
    - Required: no
    
<a name="nested-grid-view"></a>
### Nested Grid

You can insert one grid into another. To do this you need to render nested grid manually:
```php
// IN CONTROLLER
$ext = $this->get('twig')->getExtension(GridExtension::class);

$nestedGridData = [
    'dataSource' => $nestedGridDataSource,
]; 

$renderedNestedGrid = $ext->prepareGridView(
    $gridFactory->prepareGridView($nestedGridData)
);

$gridData = [
    'dataSource' => $dataSource,
    'filterEntity' => new User,
    'tableCaption' => 'Users',
    'emptyCell' => 'no data',
    'columns' => [
        ...
        [
            'content' => function($entity, $rowIndex) use ($renderedNestedGrid) {
                return $renderedNestedGrid;
            }
            'format' => ColumnFormat::RAW_FORMAT
        ]
    ]
];

return $this->render('index.html.twig', [
    'paginationInstance' => $dataSource->getPagination(),
    'gridView' => $this->get('av_grid.grid_view_factory')
        ->prepareGridView($gridData),
]);
```

<a name="grid-filters"></a>
## Grid Filters

By default grid filters are disabled. To enable them add next code to your grid configuration:
```php
$userEntity = new User;

$gridData = [
    'filterEntity' => $userEntity,
    ...
];
```
Specifying entity instance you allowed to build filter fields.

By default filter fields will be created for each entity attributes. But you
also can add filter fields for custom column.

Example:
```php
$gridData = [
    'filterEntity' => $userEntity,
    'dataSource' => $dataSource,
    'columns' => [
        [
            'label' => 'User Name',
            'attributeName' => 'name',
            'filterType' => TextType::class,
        ]
    ],
];
```
NOTE: If you won't specify filter type then symfony will try to guess it. Concerning 
this you might got an error `Unable to transform value for property path...`. The 
main reason of this is doctrine fields validation so there are two options: 
manually specify filter type like shown above or remove validation.

You can get all available filter types at symfony's [form documentation](https://symfony.com/doc/current/forms.html#built-in-field-types)
as well as filter options.

In case of using `ArrayDataSource` you should still specify filter entity (any).

Grid filter options:
- **`filterEntity`** - Entity instance that will be used to build filter form.
    - Type: `object`
    - Example: `'filterEntity' => new User,`
- **`filterUrl`** - Target url which will accept filter data. Current route will be used by default.
    - Type: `string`
    - Example: `'filterUrl' => 'custom-url'`
    
<a name="grid-filters-full-example"></a>**Full example** for `User` entity. We have next fields: `firstName`, `lastName` and `created`.
Let's create `search` method in `UserRepository`:
```php
...
public function search(array $searchParams)
{
    $queryBuilder = $this->createQueryBuilder('u');

    if (!$searchParams) {
        return $queryBuilder;
    }
    
    $firstName = $searchParams['firstName'];
    
    $lastName = $searchParams['lastName'];
    
    $created = $searchParams['created']; // Date foramt - Y-m-d (in our case)
    
    if ($firstName) {
        $queryBuilder->andWhere('u.firstName LIKE :firstName')
            ->setParameter(':firstName', '%'.$firstName.'%');
    }
    
    if ($lastName) {
        $queryBuilder->andWhere('u.lastName LIKE :lastName')
            ->setParameter(':lastName', '%'.$lastName.'%');
    }

    // I'm sure you'll have time to write code better than this
    if ($created) {
        $queryBuilder->andWhere('u.created BETWEEN :start AND :end')
        ->setParameter(':start', $created)
        ->setParameter(
            ':end',
            (new \DateTime($created))->modify('+1 day')->format('Y-m-d')
        );
    }

    return $queryBuilder;
}
...
```
Now let's describe our filters in `Controller`:
```php
$gridFactory = $this->get('av_grid.grid_view_factory');

/** @var EntityManager $em */
$em = $this->getDoctrine()->getManager();

$dataSource = $this->get('av_grid.query_data_source')
    ->setEntityName(User::class)
    ->setRootAlias('u');

$dataSource->setDataSource(
    $em->getRepository('User')->search($request->get('User'))
);

$gridData = [
    'filterEntity' => new User,
    'dataSource' => $dataSource,
];
...
// render template
```
Now filter form will be displayed and you are able to work with incoming filter data.

<a name="grid-filters-relations"></a> 
### Filters with relations 

Also you can apply filters to related entities. For example we will be using
`User` entity and related `Country` entity. Let's create dropdown filter by country.

NOTE!!!: TO BE ABLE TO USE FILTRATION ON RELATED ENTITIES YOU SHOULD SPECIFY 
THOSE ENTITIES IN YOUR QUERY BUILDER. DO NOT USE PROXY OBJECTS.

Example:
```php
[
    'attributeName' => 'country',
    'content' => function($userEntity, $rowIndex) {
        return $entity->getCountry()->getTitle();
    },
    'filterType' => EntityType::class,
    'filterFieldOptions' => [
        'class' => Country::class,
        'choice_label' => 'title'
    ]
],
```

Some explanation to example above: `attributeName` is compulsory and it's value
should be specified in `User` entity. You also can specify arbitrary value of
`attributeName` BUT selected filter value won't be remembered by form field 
UNLESS you add this field to target (`User`) entity.

<a name="columns"></a>
## Columns

Columns provides simple interface for controlling data displaying within grid.
There are three types of columns and one base interface `BaseColumn`:
- `Column`
- `ActionColumn`
- `CounterColumn`

<a name="data-column"></a>
**Column**

Responsible for rendering common data within grid. This type of column displays 
all user data such as entities or arrays. There are a bunch of display options 
that can be customize by user.

For example we will be using `QueryDataSource` of `User` entity.Our entity 
contains next fields: `firstName`, `lastName`, `age`, `email`, `created`.

Simple example:
```php
// To just display all fields of entity you can omit column configuration
'columns' => [
    ['attributeName' => 'firstName'],
    ['attributeName' => 'lastName'],
    ['attributeName' => 'age'],
    ['attributeName' => 'email'],
    ['attributeName' => 'created'],
]
```
NOTE: If we just omit `column` configuration the result will be the same.

In case of using relations you can use next syntax:
```php
'columns' => [
    [
        'label' => 'User Country'
        'content' => function($userEntity, $rowIndex) {
            return $userEntity->getCountry()->getTitle();
        }
    ]
]

OR YOU CAN USE SHORT SYNTAX

'columns' => [
    [
        'attributeName' => 'country.title'
    ]
]
```
Extended example:
```php
'columns' => [
    [
        'label' => 'User Name'
        'sortable' => false
        'attributeName' => 'firstName',
        'filterType' => EntityType::class,
        'filterFieldOptions' => [
            'class' => User::class,
            'choice_label' => 'firstName',
            'placeholder' => 'Select name',
        ]
    ],
    [
        'label' => 'User Last Name'
        'attributeName' => 'lastName'
    ],
    [
        'attributeName' => 'fullName'
        'content' => function($userEntity, $rowIndex) {
            return $userEntity->getFirstName() . ' ' . $userEntity->getLastName(); 
        },
        'filterType' => TextType::class,
        'filterFieldOptions' => [
            'mapped' => false
        ]
    ],
    [
        'attributeName' => 'created',
        'format' => [ColumnFormat::DATE_FORMAT => 'Y-m-d']
    ],
    [
        'attributeName' => 'email',
        'visible' => function() use ($someExternalObject) {
            return // some permission check logic
        }
    ],
    [
        'label' => 'Custom column',
        'content' => function($userEntity) use ($someEntity) {
            return $someEntity->getDataByUserId($userEntity->getId());
        },
        'contentOptions' => ['width' => '100px']
    ]
]
```
Column options:
- **`attributeName`** - Entity field name or key name in case of using `ArrayDataSource`.
    - Type: `string`
    - Example: `'attributeName' => 'lastName'`
    - Required: yes if there no `content` value
- **`label`** - Column header label. If not specified then `attributeName` will be used.
    - Type: `string`
    - Example: `'label' => 'Column Label'`
    - Required: yes if there no `attributeName` value
- **`encodeLabel`** - Whether the header will be encoded. False by default.
    - Type: `string`
    - Example: `'encodeLabel' => true`
    - Required: no
- **`content`** - Column cell content. This parameter can contain string value or callback function.
    - Type: `string|callable`
    - Example: `'content' => 'custom string contetn'` . Callback function example was shown above.
    - Required: no
- **`contentOptions`** - List of option that will be applied to content cell.
    - Type: `array|callable`
    - Example: `'contentOptions' => ['width' => '100px']`
    - Required: no
- **`format`** - Format of column cell data. Default `ColumnFormat::TEXT_FORMAT`
    - Type: `string`
    - Example: `'contentOptions' => ['width' => '100px']`
    - Required: no
- **`visible`** - Whether the column should be visible. Parameter can accept bool value or callable.
    - Type: `bool|callable`
    - Example: `'visible' => false`
    - Required: no
- **`sortable`** - Whether the column is sortable.
    - Type: `bool`
    - Example: `'sortable' => false`
    - Required: no
- **`filterType`** - Filter input field type. Can apply one of Symfony form input [types](https://symfony.com/doc/current/forms.html#built-in-field-types).
    - Type: `string`
    - Example: `'filterType' => EmailType::class`
    - Required: no
- **`filterFieldOptions`** - List of options that will be applied to filter input field. Text field type [options](https://symfony.com/doc/current/reference/forms/types/text.html)
    - Type: `array`
    - Required: no
- **`filterOptions`** - List of option that will be applied to filter cell.
    - Type: `array`
    - Example: `'filterOptions' => ['class' => 'custom-filter-cell-class']`
    - Required: no

<a name="counter-column"></a>
**CounterColumn**

Provides simple counter for table rows. Included by default for both data sources
`QueryDataSource` and `ArrayDataSource`.

Inherits most properties of data column.

Example:
```php
'columns' => [
    [
        'service' => 'av_grid.counter_column',
        'label' => 'My Custom Label' // # by default
    ]
]
```
<a name="action-column"></a>
**ActionColumn**

`Action columns` allows to create control buttons for each grid row. For now there
are two types of buttons: `show`, `edit`. Each button configurable. `Action columns`
will be included to grid by default IF you are using `QueryDataSource` in other 
case you should specify it manually.

NOTE: By default all action buttons will be visible and will use the current route.
Also there is no need to specify each button manually. They will be displayed by default.

Example:
```php
'columns' => [
    [
        // Custom configuration
        'service' => 'av_grid.action_column',
        'buttons' => [
            ActionColumn::SHOW => 'http://custom-url.com'
            ActionColumn::EDIT => function ($entity, $url) use ($router) {
                return $router->generate('compaign_show', ['id' => 4]);
            },
        ],
        'hiddenButtons' => [
            ActionColumn::EDIT => function ($entity, $url) {
                return $entity->getId() == 5; // If true then won't be shown
            }
        ]
    ]
]
```
Each button has two configurable options: `url`, `visibility`. Both options 
accept both a scalar value and a callback function.

If you are using callback function there are two arguments available:
- entity instance
- default button url

`buttons` and `hiddenButtons` values are optional. By default for url creation 
will be used current route with `show` path and `edit` respectively.

For example if current url is `.../admin/user/` then for `show` button will be 
generated next url `.../admin/user/{userId}/show` until you won't change it. 

## Column data format

This component allow to apply different format type to `Column` data. There are
four available formats:
- `ColumnFormat::TEXT_FORMAT` - Default column cell data format. Encodes all `html`, `js` or `twig` entities. 
- `ColumnFormat::RAW_FORMAT` - No encoding at all. Can be use to apply some `html` or `js` to column cell data.
- `ColumnFormat::TWIG_FORMAT` - Allows to use any `twig` constructions.
- `ColumnFormat::DATE_FORMAT` - Date formatting. For now there are only two date formats that can be formatted: `timestamp (int|string)`, `\DateTime`, `\DateInterval`.

Example:
```php
'columns' => [
    [
        'label' => 'Plain text'
        'content' => function ($entity) {
            return '<script>alert("hello");</script>'; // will be displayed as plain text
        },
    ],
    [
        'label' => 'Execute JS'
        'content' => function ($entity) {
            return '<script>alert("hello");</script>'; // alert message will be shown
        },
        'format' => ColumnFormat::RAW_FORMAT,
    ],
    [
        'label' => 'Format timestamp'
        'content' => function($entity) {
            // return $entity->getCreated(); // returns \DateTime 

            // return '1484204985'; // simple timestamp
        },
        'format' => [ColumnFormat::DATE_FORMAT => 'Y-m-d']
    ]
    [
        'label' => 'Twig construction'
        'content' => function($entity) {
            return "{{ 'CUSTOM TEXT'|lower }}";
        },
        'format' => ColumnFormat::FORMAT
    ]
]
```

## DataSource

This entity acts as an intermediary between the `grid` and the data source. It is
this component that creates the final query and passes the result of its execution
to the `grid` component that works with the `BaseDataSource` interface. 

There are two entities that implements `BaseDataSource`:
- QueryDataSource
- ArrayDataSource

**QueryDataSource**

`QueryDataSource` allows to pass your instance of `QueryBuilder` to grid.
 Example:
```php
// Get user entity query builder
$queryBuilder = $entityManager->getRepository('User')->createQueryBuilder('u');

// Initialize data source
$dataSource = $this->get('av_grid.query_data_source')
    ->setDataSource($queryBuilder)
    ->setRootAlias('u'); // Read below about root alias

// Create custom pagination. OPTIONAL
$pagination = $this->get('av_grid.pagination')
    ->setPageSize(5)
    ->setTotalCount(50);

// Create custom sort. OPTIONAL
$sortAttributes = [
    'u.email',
    // Custom configuration
    'name' => [
        Sort::ASC  => [
            'u.first_name' => Sort::ASC, 'u.last_name' => Sort::ASC
        ],
        Sort::DESC => [
            'u.first_name' => Sort::DESC, 'u.last_name' => Sort::DESC
        ],
    ],
]

$sort = $this->get('av_grid.sort')->setAttributes($sortAttributes);

$dataSource->setPagination($pagination)->setSort($sort);
```
NOTE: There is no need to manually configure sort and pagination for `QueryDataSource`.
Each instance of `BaseDataSource` is created with sort and pagination with default params.
For example if we did not specify sorting params then all field of `User` entity will
be sortable.

Now we can get `user` entities with sorting and pagination settings, using `fetchEntities`:
```php
// Get all user entities
$dataSource->fetchEntities();

// Get entities total count even if we did not call `setTotalCount` of pagiantion component
$dataSource->getTotalCount();
```
NOTE: As mentioned above we should specify entity alias like we did it for `User`.
Concerning this we should ALWAYS specify entity alias for `QueryDataSource`.
If you won't do this then alias will be taken from entity name and it will be `User`.
The reason of this is default sorting configuration that should know full field name.
From the other hand you will use constructions like `JOIN`.

**ArrayDataSource**

`ArrayDataSource` allows to pass your array data to grid.

Example:
```php
// Get user entity query builder
$userData = [
    ['first_name' => 'John', last_name => 'Doe', 'email' => 'john_doe@mail.com'],
    ['first_name' => 'Jane', last_name => 'Doe', 'email' => 'jane_doe@mail.com'],
    ['first_name' => 'Mike', last_name => 'Doe', 'email' => 'mike_doe@mail.com'],
];

// Initialize data source
$dataSource = $this->get('av_grid.array_data_source')
    ->setDataSource($userData);

// Create custom pagination. OPTIONAL
...

// Create custom sort. OPTIONAL
...

$sort = $this->get('av_grid.sort')->setAttributes($sortAttributes);

$dataSource->setPagination($pagination)->setSort($sort);
```
Now we can pass our `$dataSource` to grid and it will be properly displayed.

NOTE: We did not set alias `u` for sorting as in case of `QueryDataSource`.
We should use only array keys while working with `ArrayDataSource`.

<a name="sort"></a>
## Sort

The sorting component allows you to flexibly set and expand the sorting 
parameters for specific attributes. You can use this component with one of the
data source component (`QueryDataSource` or `ArrayDataSource`).

Let's assume that we have `User` entity with next fields:
- firstName
- lastName
- email

Example:
```php
$dataSource = $this->get('av_grid.data_source');
$dataSource->setDataSource(
    $entityManager->getRepository('User')->createQueryBuilder('u')
);

// Simple example
$sortAttributes = [
    'u.email',
    'u.first_name'
    'u.last_name'
]

// Extended example: sorting by multiple fields
$sortAttributes = [
    // Default sort params will be used
    'u.email',

    // Custom configuration
    'name' => [
        Sort::ASC  => [
            'u.first_name' => Sort::ASC, 'u.last_name' => Sort::ASC
        ],
        Sort::DESC => [
            'u.first_name' => Sort::DESC, 'u.last_name' => Sort::DESC
        ],
        'default' => Sort::DESC,
        'label'   => 'Name',
    ],
]

$sort = $this->get('av_grid.sort')->setAttributes($sortAttributes);

$dataSource->setSort($sort);
```
If attribute name specified without any params then default params will be used.
Next example shows how the `email` attribute will be interpreted.
```php
[
    'u.email'  => [
        Sort::ASC  => ['u.email' => Sort::ASC],
        Sort::DESC => ['u.email' => Sort::DESC],
        
    ],
]
```
The attribute names used in the configuration are the names of the entity fields
if you are using the `QueryDataSource` and the array keys name if you are using the 
`ArrayDataSource`.

NOTE: In expanded attribute configuration we used custom field title `name` but 
specified sortable fields using specified alias `u`. In case of short notation 
we should specify entity alias like we did it with `u.email`.

**Attribute options:**
- **`Sort::ASC`** - Configuration of ascend sorting. This param defines sorting by one or multiply attributes. Although this param defines ascend sorting it's allowed to use both types of sorting for any attributes.
    - Type: `array`
    - Required: no
    - Example: `Sort::ASC => ['first_name' => Sort::ASC, 'last_name' => Sort::ASC]`
- **`Sort::DESC`** - See `Sort::ASC`
    - Type: `array`
    - Required: no
    - Example: `Sort::DESC => ['first_name' => Sort::DESC, 'age' => Sort::ASC]`
- **`default`** - Default sort params. This sort type will be used by default.
    - Type: `string`
    - Required: no
    - Example: `['default' => Sort::DESC]`
- **`label`** - Attribute name that will be used in request string. If label was not defined attribute name will be used.
    - Type: `string`
    - Required: no
    - Example: `['label' => 'FullName']`

There are few additional params of `Sort` instance that can be configured:
- **`sortParam`** - The name of the parameter that will contain the sorting data in the query string. This param can be changed to allow multiply sort instances on single page. Default value `sort`.
    - Type: `string`
    - Example: `$sortInstance->setSortParam('customSortParam')`
- **`separator`** - Symbol that uses to separate sort attributes in query string. Default value `,`.
    - Type: `string`
    - Required: no
    - Example: `$sortInstance->setSeparator('.')`
- **`defaultOrder`** - Default sort params.
    - Type: `array`
    - Required: no
    - Example: `$sortInstance->setDefaultOrder(['email' => Sort::DESC])`

<a name="relation-sort"></a>
### Relation Sort

As well as filter you can sort by related entities fields. For example we will be using
`User` entity and related `Country` entity.

NOTE!!!: TO BE ABLE TO USE SORT ON RELATED ENTITIES YOU SHOULD SPECIFY 
THOSE ENTITIES IN YOUR QUERY BUILDER. DO NOT USE PROXY OBJECTS.

Example:
```php
$sort = $this->get('av_grid.sort');

$sort->setAttributes([
    'countryTitle' => [
        Sort::ASC  => ['c.title' => Sort::ASC],
        Sort::DESC => ['c.title' => Sort::DESC],
    ]
]);

$dataSource->setSort($sort);
```
Some explanation to example above: `c` is `Country` entity alias
that you should specify in you query builder.

<a name="pagination"></a>
## Pagination

Pagination component allows you to easily limit the amount of data displayed on the page.
This component consists from three parts:
- **Pagination** - responsible for the managing of the parameters of limitation and offset of `QueryBuilder`.
- **PaginationView** - responsible for the building of the pagination block. Also this component generates pagination links.
- **PaginationExtension** - twig extension for rendering pagination block.

Pagination example:
```php
$pagination = $this->get('av_grid.pagination');

// Set amount of items per page
$pagination->setPageSize(3); // Or default value will be used
$pagination->setTotalCount(12); // required if used beyond `QueryDataSource` context.

// Set custom pagination instance to QueryDataSource and override default QueryDataSource pagination config
$dataSource->setPagination($pagination);
```
Using these parameters, the pagination component is able to generate the values 
of the limit and offset based on the query parameters.
```php
// Let's assume that we have query string: .../admin/user/?per-page=3&page=3

echo $pagination->getLimit(); // 3
echo $pagination->getOffset(); // 6
```

NOTE: There might be a names conflict. For example one of your routes already uses `page` query param name.
In that case you can rename page param name:
```php
$pagination->setPageParam('my-page-counter'); // .../admin/user/?per-page=3&my-page-counter=3
```
**Pagination options:**
- **`pageSize`** - Number of items per page.
    - Type: `int`
    - Example: `$pagination->setPageSize(10)`
    - Required: no
- **`totalCount`** - Total number of items.
    - Type: `int`
    - Required: yes
    - Example: `$pagination->setTotalCount(100)`
- **`pageParam`** - Name of query parameter that contains page number. This param can be changed to allow multiply pagination blocks on single page. Default value `page`.
    - Type: `string`
    - Required: no
    - Example: `$pagination->setPageParam('customPageName') // .../?per-page=3&customPageName=3`
- **`pageSizeParam`** - Name of query parameter that contains number of items on page. This param can be changed to allow multiply pagination blocks on single page. Default value `per-page`.
    - Type: `string`
    - Required: no
    - Example: `$pagination->setPageSizeParam('customPageSize')`
- **`route`** - Name of route. If route was not specified then current route will be used.
    - Type: `string`
    - Required: no
    - Example: `$pagination->setRoute('post_index')`
- **`defaultPageSize`** - Default number of items per page. Will be used if `pageSize` was not specified. Default 20.
    - Type: `int`
    - Required: no
    - Example: `$pagination->setDefaultPageSize(50)`
    
NOTE: `BaseDataSource` instances already contains pagination instance. 
So there is no need to specify pagination instance manually and set it to 
`QueryDataSource` or `ArrayDataSource`. Instead default pagination configuration will be used.

To display pagination block call twig extension in you template:
```php
// paginationInstance should be passed to template from controller
{{ gridPagination(paginationInstance) }}
```
`gridPagination` extension can apply additional params. ALL PARAMETERS ARE OPTIONAL:
```php
{{ gridPagination(
        paginationInstance,
        {
            'options'             : {'id': 'pagination-block-id', 'data-id': 'some-id'},
            'linkOptions'         : {'class': 'class-for-each-link'},
            'showFirstPageLink'   : true,
            'showLastPageLink'    : true,
            'showPrevPageLink'    : true,
            'showNextPageLink'    : true,
            'nextPageLabel'       : '&rsaquo;',
            'prevPageLabel'       : '&lsaquo;',
            'firstPageLabel'      : '&laquo;',
            'lastPageLabel'       : '&raquo;',
            'maxButtonCount'      : 13,
            'activePageCssClass'  : 'active',
            'disabledPageCssClass': 'disabled',
            'firstPageCssClass'   : 'first',
            'lastPageCssClass'    : 'last',
            'prevPageCssClass'    : 'prev',
            'nextPageCssClass'    : 'next',
        }
   )
}}
```
