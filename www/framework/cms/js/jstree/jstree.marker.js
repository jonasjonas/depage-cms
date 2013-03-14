// {{{ add_marker
/*
 * add marker plugin
 */
(function ($) {
    $.jstree.plugin("add_marker", {
        __construct : function () {
            this.data.add_marker = {
                offset : null,
                w : null,
                target : null,
                context_menu : false,
                marker : $("<div>ADD</div>").attr({ id : "jstree-add-marker" }).hide().appendTo("body"),
                indicator : $("<div />").attr({ id : "jstree-add-marker-indicator" }).hide().appendTo("body")
            };

            var c = this.get_container();
            c.bind("mouseleave.jstree", $.proxy(function(e) {
                if (!this.data.add_marker.context_menu) {
                    this.data.add_marker.marker.hide();
                }
            }, this))
                .delegate("li", "mousemove.jstree", $.proxy(function(e) {
                if (!this.data.add_marker.context_menu) {
                    this._show_add_marker($(e.target), e.pageX, e.pageY);
                }
            }, this));

            this.data.add_marker.marker.mousemove($.proxy(function (e) {
                if (!this.data.add_marker.context_menu) {
                    // add marker swallows mousemove event. try to delegate to correct li_node.
                    // TODO: fix for Opera < 10.5, Safari 4.0 Win. see http://www.quirksmode.org/dom/w3c%5Fcssom.html#documentview
                    var element = $(document.elementFromPoint(e.clientX - this.data.add_marker.marker.width(), e.clientY));
                    this._show_add_marker(element, e.pageX, e.pageY);
                }
            }, this))
                .click($.proxy(function (e) {
                this._show_add_context_menu();
            }, this));
            $(document).bind("context_hide.vakata", $.proxy(function () {
                this.data.add_marker.context_menu = false;
                this.data.add_marker.marker.hide();
            }, this));
        },
        _fn : {
            // DEPRECATE
            /*
            _get_valid_children : function () {
                var types_settings = this.get_settings()['typesfromurl'];
                if (this.data.add_marker.parent !== -1) {
                    var parent_type = this.data.add_marker.parent.attr(types_settings.type_attr);
                    var valid_children = (types_settings.valid_children[parent_type] || types_settings.valid_children["default"]);
                } else {
                    // root element
                    var valid_children = types_settings.valid_children;
                }

                return valid_children;
            },
            _has_valid_children : function () {
                return this._get_valid_children() != "none";
            },
            _get_add_context_menu_item : function (name, separator) {
                return {
                    separator_before : separator || false,
                    separator_after : false,
                    label : "Create " + name,
                    action : function (obj) {
                        this.create(this.data.add_marker.target, this.data.add_marker.pos, { attr : { rel : name } });
                    }
                };
            },
            _get_add_context_menu_items : function () {
                var valid_children = this._get_valid_children();
                var special_children = (this.get_container().attr("data-add-marker-special-children") || "").split(" ");
                var items = [];

                if ($.isArray(valid_children)) {
                    for (var i = 0; i < special_children.length; i++) {
                        if ($.inArray(special_children[i], valid_children) != -1) {
                            items.push(this._get_add_context_menu_item(special_children[i]));
                        }
                    }

                    for (var i = 0; i < valid_children.length; i++) {
                        if ($.inArray(valid_children[i], special_children) == -1) {
                            items.push(this._get_add_context_menu_item(valid_children[i], i == 0));
                        }
                    }
                }

                return items;
            },
             */
            _show_add_context_menu : function () {
                var type_settings =  this.get_settings()['typesfromurl'];
                var type = this.data.add_marker.target.attr(type_settings.type_attr);
                var available_nodes = type_settings['valid_children'][type];

                var create_menu = $.jstree.buildCreateMenu(available_nodes, this.data.add_marker.pos);

                var position = {
                    'x' : this.data.add_marker.marker.offset()['left'],
                    'y' : this.data.add_marker.marker.offset()['top'] + this.data.core.li_height
                };

                $.vakata.context.show(
                    this.data.add_marker.target,
                    position,
                    create_menu.create.submenu);

                $(document).bind("context_hide.vakata", $.proxy(function () {
                    this.data.add_marker.context_menu = false;
                }, this));

                this.data.add_marker.context_menu = true;

                // DEPRECATE
                /*
                var items = this._get_add_context_menu_items();
                if (items.length) {
                    var a = this.data.add_marker.marker;
                    var o = a.offset();
                    var x = o.left;
                    var y = o.top + this.data.core.li_height;

                    this.data.add_marker.context_menu = true;
                    $.vakata.context.show(items, a, x, y, this, this.data.add_marker.target);
                    if(this.data.themes) { $.vakata.context.cnt.attr("class", "jstree-" + this.data.themes.theme + "-context"); }
                }
                */
            },

            _show_add_marker : function (target, page_x, page_y) {
                var node = this.get_node(target);

                if(this.data.add_marker.context_menu) {
                    return;
                }

                if (!node || node == -1 || target[0].nodeName == "UL") {
                    this.data.add_marker.marker.hide();
                    this.data.add_marker.indicator.hide();
                    return;
                }

                var c = this.get_container();
                var marker_pos = {'left' : 0, 'top' : 0};
                var indicator_pos = {'left' : 0, 'top' : 0};
                marker_pos.left = c.offset().left + c.width() - (c.attr("data-add-marker-right") || 30) - (c.attr("data-add-marker-margin-right") || 10);
                var min_x = marker_pos.left - (c.attr("data-add-marker-margin-left") || 10);
                if (page_x < min_x) {
                    this.data.add_marker.marker.hide();
                    this.data.add_marker.indicator.hide();
                    return;
                }

                // fix li_height
                this.data.core.li_height = c.find("ul li.jstree-closed, ul li.jstree-leaf").eq(0).height() || 18;
                this.data.add_marker.offset = target.offset();
                this.data.add_marker.w = (page_y - (this.data.add_marker.offset.top || 0)) % this.data.core.li_height;
                marker_pos.top = this.data.add_marker.offset.top;
                indicator_pos.top = this.data.add_marker.offset.top;

                indicator_pos.left = target.is('a')
                    ? target.position().left + target.width()
                    : target.children('a').position().left;

                if (this.data.add_marker.w < this.data.core.li_height / 4) {
                    // before
                    this.data.add_marker.parent = this.get_parent(node);
                    this.data.add_marker.target = node;
                    this.data.add_marker.pos = "before";
                    this.data.add_marker.marker.addClass("jstree-add-marker-between").removeClass("jstree-add-marker-inside");
                    marker_pos.top -= this.data.core.li_height / 2;
                    indicator_pos.top -= this.data.core.li_height / 2;
                } else if (this.data.add_marker.w <= this.data.core.li_height * 3/4) {
                    // inside
                    this.data.add_marker.parent = node;
                    this.data.add_marker.target = node;
                    this.data.add_marker.pos = "last";
                    this.data.add_marker.marker.addClass("jstree-add-marker-inside").removeClass("jstree-add-marker-between");
                    indicator_pos.left += target.children('a').width();
                } else {
                    // after
                    var target_node = this.get_next(node);
                    if (target_node.length) {
                        this.data.add_marker.parent = this.get_parent(target_node);
                        this.data.add_marker.target = target_node;
                        this.data.add_marker.pos = "before";
                    } else {
                        // special case for last node
                        this.data.add_marker.target = node.parentsUntil(".jstree", "li:last").andSelf().eq(0);
                        this.data.add_marker.parent = this.get_parent(this.data.add_marker.target);
                        this.data.add_marker.pos = "after";
                    }
                    this.data.add_marker.marker.addClass("jstree-add-marker-between").removeClass("jstree-add-marker-inside");
                    marker_pos.top += this.data.core.li_height / 2;
                    indicator_pos.top += this.data.core.li_height / 2;
                }

                // TODO check types
                /*
                if (this._has_valid_children()) {
                    this.data.add_marker.marker.removeClass("jstree-add-marker-disabled");
                } else {
                    this.data.add_marker.marker.addClass("jstree-add-marker-disabled");
                }
                */

                // indicator width
                var width = marker_pos.left - indicator_pos.left;

                // set indicator position
                this.data.add_marker.indicator.css({ "left" : indicator_pos.left + "px", "top" : indicator_pos.top + "px", "width" : width + "px"}).show();

                // set marker position
                this.data.add_marker.marker.css({ "left" : marker_pos.left + "px", "top" : marker_pos.top + "px" }).show();
            }
        }
    });
})(jQuery);
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */