module.exports = (sequelize, DataTypes) => {
    const Slideshow = sequelize.define("tbl_slide_show", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        prev_id: {
            type: DataTypes.INTEGER,
        },
        game_id: {
            type: DataTypes.INTEGER,
        },
        status: {
            type: DataTypes.TINYINT,
            comment: "0=pending,1=accept,2=reject"
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        updated_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_slide_show',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    Slideshow.associate = (models) => {
        Slideshow.belongsTo(models.user, { foreignKey: 'user_id', as: 'user' });
    };

    return Slideshow;
}