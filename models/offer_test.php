<?php

    /**
     * Sample test file using our company Testing library and Factories made by me
     */

    namespace App\Tests;
    use \MP\Factory;
    use \App\Models\User;
    use \App\Models\Scope;
    use \App\Models\ModuleScope;
    use \App\Models\ScopeOffer;

    \MP\Test\TestCase::create('Offer', function($it){
        $it->init(function(){
            User::$current = Factory\create('user', 'moderator');
        });

        $it->should('update countable columns after assign with module', function($test){
            $module_1 = Factory\build('module', 'first');
            $module_1->hours_count = 2;
            $module_1->pages_count = 2;
            $module_1->save();

            $module_2 = Factory\build('module', 'first');
            $module_2->hours_count = 3;
            $module_2->pages_count = 3;
            $module_2->save();

            $module_3 = Factory\build('module', 'first');
            $module_3->hours_count = 4;
            $module_3->pages_count = 4;
            $module_3->save();

            $scope_1 = Factory\create('scope', 'scope');
            $scope_2 = Factory\create('scope', 'scope');

            $offer = Factory\create('offer', 'offer');

            ModuleScope::build($module_1, $scope_1)->save();
            ModuleScope::build($module_2, $scope_2)->save();
            ModuleScope::build($module_3, $scope_2)->save();

            ScopeOffer::build($scope_1, $offer)->save();
            ScopeOffer::build($scope_2, $offer)->save();

            $offer->reload();

            $test->assertEquals($offer->hours_count, 9);
            $test->assertEquals($offer->pages_count, 9);
            $test->assertEquals($offer->scopes_count, 2);
        });

        $it->should('update countable columns after destroy module', function($test){
            $module_1 = Factory\build('module', 'first');
            $module_1->hours_count = 2;
            $module_1->pages_count = 2;
            $module_1->save();

            $module_2 = Factory\build('module', 'first');
            $module_2->hours_count = 3;
            $module_2->pages_count = 3;
            $module_2->save();

            $module_3 = Factory\build('module', 'first');
            $module_3->hours_count = 4;
            $module_3->pages_count = 4;
            $module_3->save();

            $scope_1 = Factory\create('scope', 'scope');
            $scope_2 = Factory\create('scope', 'scope');

            $offer = Factory\create('offer', 'offer');

            ModuleScope::build($module_1, $scope_1)->save();
            ModuleScope::build($module_2, $scope_2)->save();
            ModuleScope::build($module_3, $scope_2)->save();

            ScopeOffer::build($scope_1, $offer)->save();
            ScopeOffer::build($scope_2, $offer)->save();

            $module_1->destroy();

            $offer->reload();

            $test->assertEquals($offer->hours_count, 7);
            $test->assertEquals($offer->pages_count, 7);
            $test->assertEquals($offer->scopes_count, 2);
        });

        $it->should('update countable columns after destroy scope', function($test){
            $module_1 = Factory\build('module', 'first');
            $module_1->hours_count = 2;
            $module_1->pages_count = 2;
            $module_1->save();

            $module_2 = Factory\build('module', 'first');
            $module_2->hours_count = 3;
            $module_2->pages_count = 3;
            $module_2->save();

            $module_3 = Factory\build('module', 'first');
            $module_3->hours_count = 4;
            $module_3->pages_count = 4;
            $module_3->save();

            $scope_1 = Factory\create('scope', 'scope');
            $scope_2 = Factory\create('scope', 'scope');

            $offer = Factory\create('offer', 'offer');

            ModuleScope::build($module_1, $scope_1)->save();
            ModuleScope::build($module_2, $scope_2)->save();
            ModuleScope::build($module_3, $scope_2)->save();

            ScopeOffer::build($scope_1, $offer)->save();
            ScopeOffer::build($scope_2, $offer)->save();

            $scope_1->destroy();

            $offer->reload();

            $test->assertEquals($offer->hours_count, 7);
            $test->assertEquals($offer->pages_count, 7);
            $test->assertEquals($offer->scopes_count, 1);
        });

        $it->should('update countable columns after module edit', function($test){
            $module_1 = Factory\build('module', 'first');
            $module_1->hours_count = 2;
            $module_1->pages_count = 2;
            $module_1->save();

            $module_2 = Factory\build('module', 'first');
            $module_2->hours_count = 3;
            $module_2->pages_count = 3;
            $module_2->save();

            $module_3 = Factory\build('module', 'first');
            $module_3->hours_count = 4;
            $module_3->pages_count = 4;
            $module_3->save();

            $scope_1 = Factory\create('scope', 'scope');
            $scope_2 = Factory\create('scope', 'scope');

            $offer = Factory\create('offer', 'offer');

            ModuleScope::build($module_1, $scope_1)->save();
            ModuleScope::build($module_2, $scope_2)->save();
            ModuleScope::build($module_3, $scope_2)->save();

            ScopeOffer::build($scope_1, $offer)->save();
            ScopeOffer::build($scope_2, $offer)->save();

            $module_2->hours_count = 6;
            $module_2->pages_count = 6;
            $module_2->save();

            $offer->reload();

            $test->assertEquals($offer->hours_count, 12);
            $test->assertEquals($offer->pages_count, 12);
            $test->assertEquals($offer->scopes_count, 2);
        });
    });



