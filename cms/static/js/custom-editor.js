$.fn.scrollView = function() {
    return this.each(function() {
        $('html, body').animate({
            scrollTop: $(this).offset().top - 50
        }, 50);
    });
}

$(function() {
    var itemForm = Object.create(Nami.Form)
            .extend({
                extraData: {publication: editorParams.publicationId}
            }),
        editorTypes = editorParams.editorTypes,
        ukModal = UIkit.modal("#js-form-modal"),
        namiItemCreatePlace = "top",
        ukModalHideCallback = null,
        $dropTemplate = $(".js-drop_placeholder-template");


    var itemPositionHelper = {
        $items: $(".js_cms-items"),
        $itemForMove: null,
        moveItem: function($item, $target, callback) {
            $target.replaceWith($item);
            callback();
        },
        stopMove: function() {
            $(document).off("keydown.move-items");
            UIkit.notify.closeAll();

            this
                .$items
                .find(".js_cms-item")
                .removeClass("drop_item-moved")
                .removeClass("drop_item-fixed");

            this
                .$items
                .find(".js-drop_placeholder")
                .remove();

        },
        startMove: function() {
            var helper = this;

            $(document).on("keydown.move-items", function(e) {
                if (e.keyCode === 27) {
                    helper.stopMove();
                }
            });


            UIkit.notify($("#move_msg").html(), {timeout: 0, pos: 'bottom-left', status: 'info'});

            $(".js-stop-move").on("click", function(e) {
                e.preventDefault();

                helper.stopMove();
            });


            helper.$itemForMove.addClass("drop_item-moved");
            helper.$itemForMove.siblings().addClass("drop_item-fixed");

            $(".js_cms-items")
                .find(".js_cms-item")
                .each(function(index, value) {
                    //плейсхолдер перед списком
                    if (index === 0) {
                        $dropTemplate
                            .clone()
                            .removeClass("js-drop_placeholder-template")
                            .addClass("js-drop_placeholder")
                            .insertBefore($(this))
                            .show();
                    }

                    $dropTemplate
                        .clone()
                        .removeClass("js-drop_placeholder-template")
                        .addClass("js-drop_placeholder")
                        .insertAfter($(this))
                        .show();
                });

            helper.$itemForMove.prev(".js-drop_placeholder").remove();
            helper.$itemForMove.next(".js-drop_placeholder").remove();

            $(".drop_item-moved").scrollView();
        },
    };


    var saveSortableItems = function() {
        var objects = [];

        Builder.showAjaxLoader();
        $(Builder.selectors.item).each(function() {
            var id = $(this).attr('namiObject');
            objects.push(id);
        });

        if (objects.length) {
            var erAction = function(txt) {
                UIkit.notify("<i class='uk-icon-error'></i>&ensp;" + txt, {timeout: 0, pos: 'top-left', status: 'danger'});
            };

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: editorParams.ajaxUri,
                data: {
                    action: "save-positions",
                    items: objects,
                    publication: editorParams.publicationId,
                },
                success: function(response) {
                    Builder.hideAjaxLoader();
                    if (!response.status) {
                        erAction("Ошибка сохранения данных");
                    }
                },
                error: function() {
                    erAction("Ошибка сохранения данных");
                }
            });
        }
    };

    var killCK = function() {
        console.log("kill ck editor");
        for (var item in CKEDITOR.instances) {
            CKEDITOR.instances[item].destroy();
        }
    };


    $(".js_cms-create_item__bottom").on("click", function() {
        namiItemCreatePlace = "bottom";
    });

    $(".js_cms-create_item__top").on("click", function() {
        namiItemCreatePlace = "top";
    });


    ukModal.on("hide.uk.modal", function() {
        if (ukModalHideCallback) {
            ukModalHideCallback();
        }
    });


    itemForm
        .bind('fill', function(form, data) {
            var denyFields = ["type", "date"];

            form.find(".uk-form-row").each(function() {
                var name = $(this).find("[name]").attr("name");
                if (name && denyFields.indexOf(name) === -1) {
                    $(this)
                        .addClass("js-multi_fields")
                        .attr("block_name", name);
                }
            });

            var multiFields = form.find(".js-multi_fields");
            multiFields.hide();

            form
                .find("select[name=type]")
                .on("change", function() {
                    multiFields.hide();

                    var allowFields = editorTypes[$(this).val()].fields;

                    multiFields.each(function() {
                        if (allowFields.indexOf($(this).attr("block_name")) !== -1) {
                            $(this).show();
                        }
                    });
                })
                .trigger("change");
        })
        .bind('showitem', function(item) {
            if (this.mode === 'create') {
                saveSortableItems();
            }
        })
        .bind('fetchdata', function(form, data) {
            form
                .find(".js_cms_item_edit_form__save")
                .attr("disabled", "disabled")
                .text("сохраняю");
        });


    $(".js_cms-items")
        .on("click", ".js_cms-delete_item", function(e) {
            e.preventDefault();

            saveSortableItems();
        })
        .on("click", ".js_cms-free_move", function(e) {
            e.preventDefault();

            itemPositionHelper.$itemForMove = $(e.currentTarget).closest(".js_cms-item");
            itemPositionHelper.startMove();
        })
        .on("click", ".js_cms-sort_up", function(e) {
            e.preventDefault();


            var $currentItem = $(e.target).parents(".js_cms-item"),
                $newPosItem = $("<span></span>");

            if (!$currentItem.prev(".js_cms-item").length) {
                return false;
            }

            $newPosItem.insertBefore($currentItem.prev(".js_cms-item"));

            itemPositionHelper.moveItem($currentItem, $newPosItem, function() {
                saveSortableItems();
            });
        })
        .on("click", ".js_cms-sort_down", function(e) {
            e.preventDefault();


            var $currentItem = $(e.target).parents(".js_cms-item"),
                $newPosItem = $("<span></span>");

            if (!$currentItem.next(".js_cms-item").length) {
                return false;
            }

            $newPosItem.insertAfter($currentItem.next(".js_cms-item"));

            itemPositionHelper.moveItem($currentItem, $newPosItem, function() {
                saveSortableItems();
            });
        })
        .on("click", ".js-drop_placeholder", function(e) {
            e.preventDefault();

            itemPositionHelper.moveItem(itemPositionHelper.$itemForMove, $(e.currentTarget), function() {
                itemPositionHelper.stopMove();
                itemPositionHelper.$itemForMove.scrollView();
                saveSortableItems();
            });
        });


    Nami.Form.create = function() {
        var namiObj = this;

        namiObj.cloneForm();
        $("#js-custom-form-place").prepend(namiObj.form);

        ukModalHideCallback = function() {
            try {
                namiObj.cancel();
            } catch (e) {
                //ckeditor крашится при определенных непонятных условиях
                //перехватываем ошибку и самостоятельно убиваем все редакторы к хуям
                killCK();
            }

            $("#js-custom-form-place").empty();
        };

        ukModal.show();

        if (this.mode === 'create' && this.item) {
            this.item = this.item.clone(true).hide().removeAttr('id');
            var place = null;

            if (this.itemPlace) {
                place = this.itemPlace;
            } else {
                place = this.formPlace;
            }

            if (namiItemCreatePlace == "top") {
                place.prepend(this.item);
            } else {
                place.append(this.item);
            }
        }

        this.bindFormActions();
        this.onCreate(this.form);
        this.trigger('create', this.form);
        this.launch();
    };

    Nami.Form.show = function() {
        this.onShow(this.form);
        this.trigger('show', this.form);
        this.form.show();
        this.form.find('input[type=text]:not([datepicker=yes],[imageupload=yes], [imagesupload=yes]), textarea:not([richtext=yes])').first().focus().select();

        for (var name in this.inputs) {
            if (this.inputs.hasOwnProperty(name)) {
                $(this.inputs[name]).trigger('show', [this.loadedData[name], name, this]);
            }
        }

        return this;
    };

    Nami.Form.destroy = function() {
        var objectUri = this.object.getUri();
        if (objectUri in this.urisBeingEdited) {
            delete this.urisBeingEdited[objectUri];
        }

        this.onDestroy(this.form);
        this.trigger('destroy', this.form);
        this.form.remove();

        ukModalHideCallback = null;
        ukModal.hide();

        return this;
    };


    Builder.Interface
        .modelForm(itemForm)
        .listActions(itemForm);

});
