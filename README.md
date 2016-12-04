# Matisse

## Introduction

### What is Matisse?

Matisse is a component-based template engine for PHP web applications.

#### What are components?

**Components** are parameterised, composable and reusable units of rendering logic, domain logic and markup, that you can assemble and configure to create visual interfaces and provide functionality to applications.

#### How does Matisse differ from other templating engines?

Like any other template engine, Matisse generates an HTML document by combining a source (template) document with data from your view model.

But unlike most other PHP template engines, which just render templates made of text/markup intermixed with code written in PHP or in a custom DSL, Matisse **assembles user interfaces from building blocks** called components. It also tries to **keep logic out of templates** as much as possible, therefore allowing a **clear separation of programming and presentation logic**.

Matisse templates are very **clean and readable**, and are composed only of clean HTML enhanced with additional component tags and binding expressions.

Finally, applications made with Matisse are not MVC (Model View Controller) based.
Matisse promotes a **MVVM** (Model, View, View Model) architecture, with mono or **bi-directional data binding**.

#### How is it similar to other competing solutions?

Some concepts may remind of you of AngularJS, React or Web Components, which are client-side frameworks.

Matisse, combined with the Electro framework, brings to the server-side many of those concepts, but in a pragmatic, simpler, faster and easier way, allowing you to write sophisticated web applications without having to deal with Javascript, ES6, Typescript, AJAX, REST APIs, module loaders, transpilers, file watchers and complicated build toolchains. Just write your code, switch to the browser, hit Refresh and instantly see the changes you've made come to life. Then deploy seamlessly to production.

### What is the value proposition of Matisse?

**Matisse allows you to rapidly create complex web applications from composable building blocks.**

It reduces significantly the need to write PHP code by using components that, not only can render visual interfaces, but can also provide built-in functionality for handling data, domain logic and user interaction.

In fact, with Matisse, your entire application can be made of components, at many abstraction levels, starting from low-level design elements and widgets and progressively nesting more and more higher level abstracted concepts and constructs, up to the point where you'll be able to write your application's user interfaces with your own custom, HTML-compatible, DSL (Domain Specific Language).

With practice, your templates will become very terse, readable, semantic and expressive, but will remain flexible enough to accomodate any kind of custom HTML for handling specific requirements.

Your productivity will skyrocket by reducing code to a minimum and reusing and sharing blocks of functionality, both within projects and between projects.

Matisse optimizes the workflow between designers and programmers, by allowing designers to easily create their own components, without code, and assemble them into functional application mockups that can be converted to working prototypes by programmers with a minimum of rewriting, AND which can later return to designers for further development, remaining fully understandable to them.

Matisse also automates many of the tedious tasks web developers need to perform, by reducing the need to write boilerplate code, automatically managing assets (scripts and stylesheets), dependencies, libraries installation and integration, building and optimizing stuff for production, etc.

More than a templating engine, Matisse is an architectural framework for your applications, and a different way of developing them.

## Installation

To install this plugin on your application, using the terminal, `cd` to your app's directory and type:

```bash
workman install plugin electro-modules/matisse
```

> For correct operation, do not install this package directly with Composer.

## Overview

Matisse is a powerful templating system and explaining all its capabilities is beyond the scope of this Readme.

We'll give you just a little overview of its main concepts and features, so that you may have a cursory idea of what it is and how it can be useful for rapidly creating complex web applications from composable building blocks.

#### Templates

Matisse templates are HTML text files where, besides common HTML tags (always lower cased), special tags, beginning with a capital letter, specify dynamic components.

##### Example

```HTML
<h1>Some HTML text</h1>
<form>
  <Input name=field1 value={myVar}/>
  <For each=record of={data}>
    <Header><ul></Header>
    <li>Item {record.name}</li>
    <Footer></ul></Footer>
    <Else>There are no items.</Else>
  </For>
</form>
```

> **Note:** when writing templates, HTML markup should be written in HTML 5 syntax, while component tags must be written in XML syntax. This means tags must always be closed, even if the tag has no content (you can use the self-closing tag syntax: `<Component/>`). Unlike XML though, attribute values are not required to be enclosed in quotes.

#### Components

On the example above, `Input` is a component, not the common `input` HTML element. It is processed on the server, and it generates HTML markup that will replace its tag on the resulting HTML document.

`For` is another component. It repeats a block of markup for each record on a list (array) of records.
When there is data, `For` writes an header (if specified), followed by the repeatable content, followed by a footer (if specified).
If there is no data, only the content of the `Else` subtag is output.

#### Attributes

Component properties are represented by HTML tag attributes (ex. the `each` and `of` attributes of the `For` tag).

You should **use attributes for defining properties having scalar values** (ex: string or numeric values).

The syntax for an attribute is `name="value"`, `name='value'` or `name=value` (the later can be used only if the value has no spaces on it).

Boolean attributes (those with `true` or `false` values) can be defined without specifiyng the values. Ex: `<Tag readonly/>` for `true` and just `<Tag/>` (with the attribute missing) for `false`.

#### Subtags

Subtags, like tags, are also capitalized, but they do not represent components; they represent properties of the enclosing component.

In the example above, the optional content parts of the `For` component are defined with subtags: the `Header`, `Footer` and `Else` tags.

**Use subtags when the property value is HTML markup**. Notice that a subtag's markup content may define additional components.

#### Mixed HTML and XML markup

Again on the example above, notice how the `<ul>` tag is only closed inside the `<Footer>` tag, seemingly violating the correct HTML tag nesting structure of the template. In reality, the template is perfectly valid and so is the generated HTML output. This happens because, for Matisse, all HTML tags are simply raw text, without any special meaning. All text lying between component tags (those beginning with a capital letter) is converted into as few as possible Text components.

So, the real DOM (as parsed by Matisse) for the example above is (in pseudo-code):

```HTML
<Text value="<h1>Some HTML text</h1><form>"/>
<Input name=field1 value={myVar}/>
<For each=record of={data} header="<ul>" footer="</ul>" else="There are no items.">
  <Text value="<li>Item "/>
  <Text value={record.name}/>
  <Text value="</li>"/>
</For>
<Text value="</form>"/>
```

#### Data binding

Data from a view model or from component properties can be automatically applied to specific places on the template. This is called "binding" data to the template.

To bind data, you use "data binding expressions", which are enclosed in brackets.

##### Example

```HTML
<SomeComponent value={record.name}/>
```

If you need to insert a binding expression on a javascript block containing brackets, you'll need to always insert a line break after each bracket that is **not** part of a binding expression.

##### Example

```HTML
<script>
if (x>y) {
  alert("Hello {name}, how are you?");
}
</script>
```

> In this example, the bracket on the first line of script is not mistaken for a binding expression delimiter, as it is immediately followed by a line break.

#### Expression syntax

The syntax for expressions differs from PHP expressions. For instance, accessing properties of an object or array is done with the dot operator, instead of the `[]` or `->` operators.

Expressions can also define sequences of filters for applying multiple transformations to a value. The pipe operator `|` delimits the filters on an expression. The value from the leftmost part of the expression will flow from left to right, the result of the previous filter being fed to the next one. Filters may also have additional arguments.

##### Example

```HTML
<SomeComponent value={record.date|datePart|else 'No date'}/>
```

#### (Un)escaping output

Binding expressions always HTML-encode (escape) their output, for security reasons.

But if you really need to output raw markup, you can use the `*` filter.

##### Example

```HTML
<div>{content|*}</div>
```

#### Binding component properties

On composite components (those having their own templates), you can bind to the properties of the component (that owns the template) using the `@` operator.

##### Example

```HTML
<SomeComponent value={@someProperty}/>
```

You'l see more examples of this type of binding on the section about "Macros", below.

### Implementing your own components

Each component tag is converted into an instance of a corresponding PHP class. When the template is rendered, each class instance is responsible for generating an HTML representation of the corresponding component, together with optional (embedded or external) javascript code and stylesheet references or embedded CSS styles.

##### Minimal component example

This is the smallest, simplest component that you may implement:

```PHP
use Matisse\Components\Base\Component;

class HelloWorld extends Component
{
  protected function render ()
  {
    echo "Hello World!";
  }
}
```

You would call this component from a template like this:

```HTML
<HelloWorld/>

or

<HelloWorld></HelloWorld>
```

which would render:

```HTML
Hello World!

or

Hello World!
```

##### Minimal component with a property

Let's make our component's output text parameterizable:

```PHP
use Matisse\Components\Base\Component;
use Matisse\Properties\Base\ComponentProperties;

class MessageProperties extends ComponentProperties
{
  public $value = '';
}

class Message extends Component
{
  const propertiesClass = TextProperties::class;

  protected function render ()
  {
    echo $this->props->value;
  }
}
```

You would call this component from a template like this:

```HTML
<Message value="some text"/>

or

<Message>
  <Value>some other text</Value>
</Message>
```

which would render:

```HTML
some text

or

some other text
```

#### Macros

Components can also be defined with pure markup via template files, without any PHP code. Those templates are conceptually similar to parametric macros, so they are called *macro components*, or simply *macros*.

##### A more advanced component

This template defines a macro component that implements a customisable panel:

```HTML
<Macro name=Panel defaultParam=content>
  <Param name=type type=string default="box-solid box-default"/>
  <Param name=title type=string/>
  <Param name=content type=content/>
  <Param name=footer type=content/>

  <div class="panel box {@type}">
    <If {@title}>
      <div class="box-header with-border">
        <h3 class="box-title">{@title}</h3>
      </div>
    </If>
    <div class="box-body">
      {@content|*}
    </div>
    <If {@footer}>
      <div class="box-footer">
        {@footer|*}
      </div>
    </If>
  </div>
</Template>
```

You can then create instances of this component like this:

```HTML
<Panel type="info" title="My title">
  <h1>Welcome</h1>
  <p>Some text here...</p>
  <Footer>Some footer text...</Footer>
</Panel>
```

When rendered, the template will generate the following HTML markup:

```HTML
<div class="panel box box-info">
  <div class="box-header with-border">
    <h3 class="box-title">My title</h3>
  </div>
  <div class="box-body">
    <h1>Welcome</h1>
    <p>Some text here...</p>
  </div>
  <div class="box-footer">
    Some footer text...
  </div>
</div>
```

### View Models

You may bind values from a view model into your template.

For instance, given the following view model:

```PHP
$model = ['footerText' => 'Some footer text...'];
```

you may call the same component, defined above, like this:

```HTML
<Panel type="box-info" title="My title">
  <h1>Welcome</h1>
  <p>Some text here...</p>
  <Footer>{footerText}</Footer>
</Panel>
```

The resulting output would be identical to the one from the previous example.

### More documentation

This was just a very short introduction to the Matisse template engine.

Matisse provides many more advanced features to power-up your development (like Includes, Content Blocks, Typed Properties, Composite Components, Assets Management, Rule-based Presets, Metadata, Custom Filters, Auto-mapped Controllers, Service Importing, Javascript Code Generation and Client-side API, HTTP request handling and routing, and many more!...).

Despite its current lack of documentation, Matisse is quite ready for use. In fact, we are using it, right now, on several projects at our company.

We're sorry for the lack of documentation, but we're working on it.

### See also

Take a look at the [Matisse Components](https://github.com/electro-modules/matisse-components) plugin, if you want an extensive collection of Bootstrap-based components that you can use right away on your applications.

## License

The Electro framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Electro framework** - Copyright &copy; Cláudio Silva and Impactwave, Lda.
