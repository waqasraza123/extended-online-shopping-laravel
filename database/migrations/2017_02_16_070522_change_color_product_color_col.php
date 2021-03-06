<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColorProductColorCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('color_products', function (Blueprint $table) {
            if(Schema::hasColumn('color_products', 'color')){
                $table->dropColumn('color');
            }
            if(!Schema::hasColumn('color_products', 'color_id')){
                $table->unsignedInteger('color_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('color_product', function (Blueprint $table) {
            //
        });
    }
}
