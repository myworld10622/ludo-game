module.exports = (sequelize, DataTypes) => {
    const ShareWallet = sequelize.define("tbl_share_wallet", {
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
        table_id: {
            type: DataTypes.INTEGER,
        },
        status: {
            type: DataTypes.TINYINT,
        },
        added_date: {
            type: DataTypes.DATE
        },
        updated_date: {
            type: DataTypes.DATE
        },
    }, {
        tableName: 'tbl_share_wallet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    });

    ShareWallet.associate = (models) => {
        ShareWallet.belongsTo(models.user, { foreignKey: 'user_id', as: 'tbl_users' });
        ShareWallet.belongsTo(models.user, { foreignKey: 'to_user_id', as: 'to_user' });
    };

    return ShareWallet
}