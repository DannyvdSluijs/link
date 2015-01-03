ProcessManager.ManagerCreateController = Ember.ObjectController.extend({
    actions : {
        setDataTypeSelectFocused : function () {
            this.set("dataTypeSelectFocused", true);
        },
        unsetDataTypeSelectFocused : function () {
            this.set("dataTypeSelectFocused", false);
        },
        createProcess : function () {

            var process = this.store.createRecord('process', {
                "name" : this.get("processName"),
                "processType" : this.get("processType"),
                "startMessage" : {
                    messageType : this.get("messageType"),
                    dataType : this.get("dataType")
                },
                "tasks" : []
            });
            var con = this;

            process.save().then(function () {
                con.transitionToRoute('manager');
            }).catch($.failNotify);
        }
    },

    processType : null,
    messageType : null,
    dataType    : null,

    isNonProcessSelected    : Ember.computed.empty("processType"),
    isAProcessSelected      : Ember.computed.notEmpty("processType"),
    isLinearProcess         : Ember.computed.equal("processType", Em.I18n.t('process.linear.value')),
    isForeachProcess        : Ember.computed.equal("processType", Em.I18n.t('process.foreach.value')),

    isNonMessageSelected    : Ember.computed.empty("messageType"),
    isAMessageTypeSelected  : Ember.computed.notEmpty("messageType"),
    isCollectDataMessage    : Ember.computed.equal("messageType", Em.I18n.t('message.collect_data.value')),
    isDataCollectedMessage  : Ember.computed.equal("messageType", Em.I18n.t('message.data_collected.value')),

    isDataTypeSelected      : Ember.computed.notEmpty("dataType"),

    dataTypeSelectFocused : false,
    isNotDataTypeSelectFocused : function () {
        return !this.get("dataTypeSelectFocused");
    }.property("dataTypeSelectFocused"),

    isNotValid : function () {
        return this.get("isNonMessageSelected")
            || this.get("isNonProcessSelected")
            || ! this.get("isDataTypeSelected");
    }.property("isNonMessageSelected", "isNonProcessSelected", "isDataTypeSelected"),

    processName : function () {
        var name = "";

        if (this.get("processType") == Em.I18n.t('process.linear.value')) name = name + "Linear ";
        if (this.get("processType") == Em.I18n.t('process.foreach.value')) name = name + "Foreach ";
        if (this.get("messageType") == Em.I18n.t('message.collect_data.value')) name = name + "Collect ";
        if (this.get("messageType") == Em.I18n.t('message.data_collected.value')) name = name + "Process ";
        if (this.get("dataType") != null) name = name + this.get("dataType").split("\\").pop() + " ";

        return name;
    }.property("processType", "messageType", "dataType")
});

ProcessManager.ManagerCreateRoute = Ember.Route.extend({
    model : function () {
        return {
            processTypes : [
                {
                    value : Em.I18n.t('process.linear.value'),
                    label : Em.I18n.t('process.linear.label')
                },
                {
                    value : Em.I18n.t('process.foreach.value'),
                    label : Em.I18n.t('process.foreach.label')
                }
            ],
            messageTypes : [
                {
                    value : Em.I18n.t('message.collect_data.value'),
                    label : Em.I18n.t('message.collect_data.label')
                },
                {
                    value : Em.I18n.t('message.data_collected.value'),
                    label : Em.I18n.t('message.data_collected.label')
                }
            ],
            dataTypes : ProcessManager.DataTypes
        }
    }
});