module.exports = (sequelize, DataTypes) => {

    const Tip = sequelize.define("tbl_tip_log", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        to_user_id: {
            type: DataTypes.INTEGER,
        },
        gift_id: {
            type: DataTypes.INTEGER,
        },
        table_id: {
            type: DataTypes.INTEGER,
        },
        coin: {
            type: DataTypes.BIGINT
        },
        added_date: {
            type: DataTypes.DATE
        },
    }, {
        tableName: 'tbl_tip_log',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    Tip.associate = (models) => {
        Tip.belongsTo(models.Gift, { foreignKey: 'gift_id', as: 'gift' });
    };
    return Tip
}