# Matisse

## What is Matisse?

Matisse is a component-based template engine for PHP web applications.

Like any other template engine, Matisse generates an HTML document by combining a source (template) document with data from your view model.

Unlike most other PHP template engines, which deal with HTML markup with embedded commands written on some DSL, Matisse works with components, which are parametrised, composable and reusable units of rendering logic and markup that are written as XML tags.

The source template is an HTML text file where, besides common HTML tags (always lower cased), special tags beginning with a capital letter specify dynamic components.

Example of a Matisse template:

```HTML
<h1>Some HTML text</h1>
<form>
	<Input name="field1" value="{myVar}"/>
	<For each="{data}" as="name">
		<Header><ul></Header>
		<li>Item {name}</li>
		<Footer></ul></Footer>
		<Else>The are no items.</Else>
	</For>
</form>
```

> When writing templates, HTML markup should be written in HTML 5 syntax, while component tags must be written in XML syntax. This means tags must always be closed, even if the tag has no content (you can use the self-closing tag syntax: `<Component/>`). Unlike XML though, attribute values are not required to be enclosed in quotes.

On the example above, notice how the `<ul>` tag is only closed inside the `<Footer>` tag, seemingly violating the correct HTML tag nesting structure of the template. In reality, the template is perfectly valid and so is the generated HTML output. This happens because, for Matisse, all HTML tags are simply raw text, without any special meaning. All text lying between component tags (those beginning with a capital letter) is converted into as few as possible Text components.

So, the real DOM (as parsed by Matisse) for the example above is:

```HTML
<Text/>
<Input/>
<For>
	<Header/>
	<Text/>
	<Footer/>
	<Else/>
</For>
<Text/>
```

### Components implementation

Each component tag is converted into an instance of a corresponding PHP class. When the template is rendered, each component instance is responsible for generating an HTML representation of that component, together with optional (embedded or external) javascript code and stylesheet references or embedded CSS styles.

Components can also be defined with pure markup via template files, without any PHP code. Those templates are conceptually similar to parametric macros, so they are called *macro components*, or simply *macros*.

A more advanced example of a Matisse template, which defines a macro component that implements a customisable panel:

```HTML
<Template name="Form" defaultParam="content">
  <Param name="type" type="text" default="box-solid box-default"/>
  <Param name="title" type="text"/>
  <Param name="content" type="source"/>
  <Param name="footer" type="source"/>

  <div class="form box {@type}">
    <If {@title}>
      <div class="box-header with-border">
        <h3 class="box-title">{@title}</h3>
      </div>
    </If>
    <div class="box-body">
      {@content}
    </div>
    <If {@footer}>
      <div class="box-footer">
        {@footer}
      </div>
    </If>
  </div>
</Template>
```

You can then create instances of this component like this:

```HTML
<Form type="box-info" title="My title">
  <h1>Welcome</h1>
  <p>Some text here...</p>
  <Footer>
    Some footer text...
  </Footer>
</Form>
```

If the following view model is defined:

```PHP
[
  'footerText' => 'Some footer text...'
]
```

When rendered, the template will generate the following HTML markup:

```HTML
<div class="form box box-info">
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

### More documentation

This was just a very short introduction to the Matisse template engine. Matisse provides many more advanced features for you to use on your templates.

Matisse is already quite functional and it's being used right now on several projects at our company.

We are sorry for the current lack of documentation. We are working on it, but we are also continually improving, not only Matisse, but also the containing ecosystem comprised by the Selenia framework and its many modules.

## License

The Selenia framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Selenia framework** - Copyright &copy; 2015 Impactwave, Lda.
