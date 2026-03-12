module.exports = (sequelize, DataTypes) => {
    const HeadTailMap = sequelize.define("tbl_head_tail_map", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        head_tail_id: {
            type: DataTypes.INTEGER,
        },
        card: {
            type: DataTypes.STRING,
            allowNull: true,
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_head_tail_map',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
    })

    HeadTailMap.associate = (models) => {
        HeadTailMap.belongsTo(models.HeadTail, { foreignKey: 'head_tail_id', as: "head_tail" });
    };

    return HeadTailMap;
}