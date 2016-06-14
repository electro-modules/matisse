<?php
namespace Selenia\Matisse\Properties\TypeSystem;

/**
 * Defines additional constants that can be used on the DSL of data type declarations of component properties.
 */
class is
{
  /**
   * Declares that a property is of an enumerated type.
   * <p>The set of allowed values for the property is specified as an array argument following this keyword.
   * >**Ex:**
   * ```
   *   public $align = [type::string, is::enum, ['left', 'center', 'right']]
   * ```
   */
  const enum = '~enum';
  /**
   * Declares the type of the elements of a collection property.
   * <p>The type is specified as the next value following this keyword on the declaration array.
   * >**Ex:**
   * ```
   *   public $column = [type::collection, is::of, type::content]
   * ```
   */
  const of = '~of';
  /**
   * Declares that a property is mandatory (i.e. it must be specified on every instance).
   * >**Ex:**
   * ```
   *   public $title = [type::string, is::required]
   * ```
   */
  const required = '~req';
}
