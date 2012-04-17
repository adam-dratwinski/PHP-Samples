<?php

/**
 * Below is sample PHP model using our Active Record library. It 
 * also show how large is application model (contains over 100 db tables)
 */

namespace App\Models;

use App\Models;
use App\Models\Task;
use App\Models\User;
use App\Models\Message\Message;
use App\Message\Messenger;

class Product extends \App\ORM\Base
{
    const ACTION_PRODUCT_STARTED        = 1;
    const ACTION_SCHEDULE_ACCEPTED      = 2;
    const ACTION_PARTICIPATION_ACCEPTED = 3;
    const ACTION_CASTING_STARTED        = 4;
    const ACTION_CASTING_CLOSED         = 5;
    const ACTION_PRODUCT_REALIZED       = 6;
    const ACTION_PARTICIPATION_CHANGED  = 7;
    const ACTION_TRAINERS_ASSIGNMENT_ACCEPTED = 8;
    const ACTION_TRAINING_LOCATION_ACCEPTED = 9;
    const ACTION_CATERING_ORDERED   = 10;
    const ACTION_SCHEDULE_CHANGED   = 11;

    const ACTION_CATERING_ORDER_SENT = 12;
    const ACTION_MATERIALS_ORDERED = 13;
    const ACTION_MATERIALS_ORDER_SENT = 14;

    const ACTION_TIMESHEET_ACCEPTED = 15;

    protected static $table_name = 'products';
    protected static $serialized_fields = array('contract_attachment');
    protected static $logging = TRUE;
    protected static $versioning = TRUE;


    protected static $default_order = array(
        'column'    => 'start_date',
        'direction' => 'ASC'
    );

    protected static $relations = array(
        'product_type' => array(
            'class_name'       => 'App\\Models\\Dictionaries\\ProductType',
            'type'             => 'belongs_to',
            'foreign_key'      => 'product_type_id',
            'friendly_name'    => 'Typ produktu',
            'value_pattern'    => ':name',
            'tree_parent_name' => 'parent_id',
            'validate'         => array(
                'presence',
            ),
        ),
        'project' => array(
            'class_name'    => 'App\\Models\\Project',
            'type'          => 'belongs_to',
            'foreign_key'   => 'project_id',
            'friendly_name' => 'Projekt nadrzędny',
            'value_pattern' => ':code :name',
        ),
        'support_kind' => array(
            'class_name'    => 'App\\Models\\Dictionaries\\SupportKind',
            'type'          => 'belongs_to',
            'foreign_key'   => 'support_kind_id',
            'friendly_name' => 'Rodzaj wsparcia',
            'value_pattern' => ':name',
        ),
        'old_project' => array(
            'class_name'    => 'App\\Models\\Project',
            'type'          => 'belongs_to',
            'foreign_key'   => 'old_project_id',
            'friendly_name' => 'Projekt nadrzędny',
            'value_pattern' => ':code :name',
        ),
        'organisation' => array(
            'class_name'    => 'App\\Models\\Dictionaries\\Organisation',
            'type'          => 'belongs_to',
            'foreign_key'   => 'organisation_id',
            'friendly_name' => 'Organizacja',
            'value_pattern' => ':name',
        ),
        'companies' => array(
            'class_name'    => 'App\\Models\\Company',
            'type'          => 'has_and_belongs_to_many',
            'foreign_key'   => 'company_id',
            'own_key'       => 'product_id',
            'connect_table' => 'company_assignments',
            'friendly_name' => 'Przypisane firmy',
            'value_pattern' => ':name',
        ),
        'branches' => array(
            'class_name'    => 'App\\Models\\CompanyBranch',
            'type'          => 'has_and_belongs_to_many',
            'foreign_key'   => 'company_branch_id',
            'own_key'       => 'product_id',
            'connect_table' => 'company_assignments',
            'friendly_name' => 'Przypisane firmy',
            'value_pattern' => ':name',
        ),
        'company_assignments' => array(
            'class_name'    => 'App\\Models\\CompanyAssignment',
            'type'          => 'has_many',
            'foreign_key'   => 'product_id',
        ),
        'participants' => array(
            'class_name'    => 'App\\Models\\Participant',
            'type'          => 'has_and_belongs_to_many',
            'foreign_key'   => 'participant_id',
            'own_key'       => 'product_id',
            'connect_table' => 'participant_assignments',
            'friendly_name' => 'Uczestnicy',
        ),
        'participations' => array(
            'class_name'    => 'App\\Models\\ParticipantAssignment',
            'type'          => 'has_many',
            'foreign_key'   => 'product_id',
        ),
        'participant_assignments' => array(
            'class_name'    => 'App\\Models\\ParticipantAssignment',
            'type'          => 'has_many',
            'foreign_key'   => 'product_id',
            'friendly_name' => 'Uczestnictwa w produkcie',
        ),
        'modules' => array(
            'class_name'    => 'App\\Models\\Module',
            'type'          => 'has_and_belongs_to_many',
            'foreign_key'   => 'module_id',
            'own_key'       => 'product_id',
            'connect_table' => 'products_modules',
            'friendly_name' => 'Moduły',
        ),
        'product_modules' => array(
            'class_name'    => 'App\\Models\\Products\\ProductModules',
            'type'          => 'has_many',
            'foreign_key'   => 'product_id',
        ),
        'schedules' => array(
            'class_name'    => 'App\\Models\\Products\\ProductSchedule',
            'type'          => 'has_many',
            'foreign_key'   => 'product_id',
        ),
        'schedule_days' => array(
            'class_name'    => 'App\\Models\\Products\\ScheduleDay',
            'type'          => 'has_many',
            'foreign_key'   => 'product_id',
        ),
        'state' => array(
            'class_name'    => 'App\\Models\\Dictionaries\\AdministrativeUnit',
            'type'          => 'belongs_to',
            'foreign_key'   => 'state_id',
            'friendly_name' => 'Województwo',
            'value_pattern' => ':name',
            'where'         => 'parent_id = 0',
        ),
        'guardians' => array(
            'class_name'    => 'App\\Models\\Users\\Guardian',
            'type'          => 'has_and_belongs_to_many',
            'own_key'       => 'product_id',
            'foreign_key'   => 'guardian_id',
            'connect_table' => 'product_guardians',
            'friendly_name' => 'Opiekunowie projektu',
        ),
        'main_guardian' => array(
            'class_name'    => 'App\\Models\\Users\\Guardian',
            'type'          => 'has_and_belongs_to_many',
            'own_key'       => 'product_id',
            'foreign_key'   => 'guardian_id',
            'connect_table' => 'product_guardians',
            'friendly_name' => 'Opiekunowie projektu',
            'where'         => 'is_main = 1',
        ),
        'interested_trainers' => array(
            'class_name'    => 'App\\Models\\Users\\Trainer',
            'type'          => 'has_and_belongs_to_many',
            'own_key'       => 'product_id',
            'foreign_key'   => 'trainer_id',
            'connect_table' => 'product_trainers',
            'friendly_name' => 'Trenerzy produktu',
        ),

        'products_trainers' => array(
            'class_name'    =>  'App\\Models\\Products\\ProductTrainer',
            'type'          => 'has_many',
            'foreign_key'   => 'product_id'
        ),
        'castings' => array(
            'class_name'    => 'App\\Models\\Products\\ProductTrainer',
            'type'          => 'has_many',
            'foreign_key'   => 'product_id',
            'friendly_name' => 'Castingi',
        ),
        'files' => array(
            'class_name'   => 'App\\Models\\Archive\\File',
            'type'         => 'has_many',
            'foreign_key'  => 'product_id',
        ),
        'company' => array(
            'type'          => 'belongs_to',
            'class_name'    => 'App\\Models\\Company',
            'foreign_key'   => 'company_id',
            'friendly_name' => 'Firma <span class="required-element">*</span>',
            'value_pattern' => ':name',
            'validate' => array(
                'presence' => array(
                    'on' => 'company_required',
                ),
            ),
        ),
        'address_branch' => array(
            'class_name'    => 'App\\Models\\CompanyBranch',
            'type'          => 'belongs_to',
            'foreign_key'   => 'address_branch_id',
            'friendly_name' => 'Miejsce szkolenia (oddział firmy)',
            'value_pattern' => ':name',
        ),
        'materials_manager' => array(
            'class_name'    => 'App\\Models\\Users\\Assistant',
            'type'          => 'belongs_to',
            'foreign_key'   => 'materials_manager_id',
            'friendly_name' => 'Asystent produktu',
            'value_pattern' => ':first_name :last_name',
            'autocomplete'  => TRUE,
            'validate' => array(
                'presence'
            )
        ),
        'messages_templates' => array(
            'class_name' => 'App\\Models\\Message\\MessageTemplate',
            'type'       => 'has_many',
            'foreign_key'=> 'product_id',
            'on_destroy'  => 'destroy'
        ),
        'tasks'         => array(
            'class_name'  => 'App\\Models\\Task',
            'type'        => 'has_many',
            'foreign_key' => 'product_id',
            'on_destroy'  => 'destroy'
        ),
        'send_order_materials_task' => array(
            'class_name' => 'App\\Models\\Task',
            'type'       => 'belongs_to',
            'foreign_key'=> 'send_order_materials_task_id',
        ),
        'send_order_catering_task' => array(
            'class_name' => 'App\\Models\\Task',
            'type'       => 'belongs_to',
            'foreign_key'=> 'send_order_catering_task_id',
        ),
        'order_materials_task' => array(
            'class_name' => 'App\\Models\\Task',
            'type'       => 'belongs_to',
            'foreign_key'=> 'order_materials_task_id',
        ),
        'order_catering_task' => array(
            'class_name' => 'App\\Models\\Task',
            'type'       => 'belongs_to',
            'foreign_key'=> 'order_catering_task_id',
            'value_pattern' => ':is_completed',
            'friendly_name' => 'Zamówiony catering'
        )
    );

    protected static $columns = array(
        'product_type_id' => array(
            'type' => 'integer',
            'index' => 'multi',
        ),
        'project_id' => array(
            'type' => 'integer',
            'index' => 'multi',
        ),
        'old_project_id' => array(
            'type' => 'integer',
            'index' => 'multi',
        ),
        'code' => array(
            'type'          => 'string',
            'friendly_name' => 'Kod produktu',
            'validate'      => array(
                'presence',
            ),
        ),
        'name_pl' => array(
            'type'          => 'string',
            'friendly_name' => 'Nazwa polska',
            'validate'      => array(
                'presence',
            ),
        ),
        'name_de' => array(
            'type'          => 'string',
            'friendly_name' => 'Nazwa niemiecka',
        ),
        'name_en' => array(
            'type'          => 'string',
            'friendly_name' => 'Nazwa angielska',
        ),
        'contract_number' => array(
            'type'          => 'string',
            'friendly_name' => 'Numer umowy',
        ),
        'contract_attachment' => array(
            'type'          => 'file',
            'friendly_name' => 'Skan umowy',
        ),
        'mpk_number' => array(
            'type'          => 'string',
            'friendly_name' => 'Numer konta',
        ),
        'organisation_id' => array(
            'type' => 'integer',
            'index' => 'multi',
        ),
        'start_date' => array(
            'type' => 'date',
            'friendly_name' => 'Data rozpoczęcia',
            'validate'      => array(
                'presence',
            ),
        ),
        'end_date' => array(
            'type' => 'date',
            'friendly_name' => 'Data zakończenia',
            'validate'      => array(
                'presence',
            ),
        ),
        'casting_end_date' => array(
            'type' => 'date',
            'friendly_name' => 'Termin nadsyłania zgłoszeń',
            'can_be_null'   => TRUE,
        ),

        'hours_count' => array(
            'type'          => 'integer',
            'friendly_name' => 'Liczba godzin',
        ),
        'description' => array(
            'type'          => 'text',
            'friendly_name' => 'Uwagi dodatkowe',
        ),
        'participant_has_company' => array(
            'type' => 'boolean',
            'friendly_name' => 'Powiązanie uczestnika z firmą',
            'available_values' => array(
                0 => 'nie',
                1 => 'tak',
            ),
            'hint' => 'W produktach, w których występuje powiązanie uczestnika z firmą, aby przypisać uczestników do produktu, należy w pierwszej kolejności przypisać do produktu firmy, których są pracownikami.',
        ),
        'support_kind_id' => array(
            'type' => 'integer',
            'index' => 'multi',
        ),
        'training_place_id' => array(
            'type' => 'integer',
            'index' => 'multi',
        ),
        'casting_status' => array(
            'type' => 'boolean',
            'friendly_name' => 'Uruchomiony casting',
            'available_values' => array(
                0 => 'n/d',
                1 => 'TAK',
                2 => 'NIE',
            ),
        ),
        'is_registration_open' => array(
            'type' => 'boolean',
            'friendly_name' => 'Uruchomiona rejestracja',
            'available_values' => array(
                0 => 'nie',
                1 => 'tak',
            ),
        ),
        'place_name' => array(
            'type' => 'string',
            'friendly_name' => 'Nazwa',
        ),
        'address' => array(
            'type' => 'string',
            'friendly_name' => 'Ulica'
        ),
        'postcode' => array(
            'type' => 'string',
            'friendly_name' => 'Kod pocztowy'
        ),
        'city' => array(
            'type' => 'string',
            'friendly_name' => 'Miejscowość'
        ),
        'city_locative_case' => array(
            'type' => 'string',
            'friendly_name' => 'Miejscowość (miejscownik)'
        ),
        'state_id' => array(
            'type' => 'integer',
            'index' => 'multi'
        ),
        'map_link' => array(
            'type' => 'text',
            'friendly_name' => 'Adres mapy Google',
            'hint' => 'W celu wygenerowania adresu przejdź na stronę google.maps.com, znajdź miejsce docelowe, następnie kliknij opcję "Link", skopiuj adres z drugiego pola i wklej tutaj.'
        ),
        'contact_person_first_name' => array(
            'type' => 'string',
            'friendly_name' => 'Imię osoby kontaktowej'
        ),
        'contact_person_last_name' => array(
            'type' => 'string',
            'friendly_name' => 'Nazwisko osoby kontaktowej'
        ),
        'phone' => array(
            'type' => 'string',
            'friendly_name' => 'Telefon kontaktowy'
        ),
        'info' => array(
            'type' => 'text',
            'friendly_name' => 'Informacje dodatkowe'
        ),
        'catering_time' => array(
            'type'          => 'time',
            'friendly_name' => 'Godzina cateringu',
        ),
        'catering_company' => array(
            'type'          => 'text',
            'friendly_name' => 'Informacje o firmie organizującej catering',
        ),
        'catering_info' => array(
            'type'          => 'text',
            'friendly_name' => 'Informacje dodatkowe',
        ),
        'company_id' => array(
            'type' => 'integer',
            'index' => 'multi',

        ),
        'copy_address_from_branch' => array(
            'type' => 'boolean',
            'friendly_name' => 'Miejsca szkolenia - siedziba firmy',
            'available_values' => array(
                0 => 'nie',
                1 => 'tak',
            ),
        ),
        'address_branch_id' => array(
            'type' => 'integer',
            'index' => 'multi'
        ),
        'schedule_accepted' => array(
            'type' => 'boolean',
            'default' => 0,
            'friendly_name' => 'Zaakceptowano harmonogram'
        ),
        'participation_accepted' => array(
            'type' => 'boolean',
            'default' => 0,
            'friendly_name' => 'Zaakceptowano uczestników'
        ),
        'trainers_assignment_accepted' => array(
            'type' => 'boolean',
            'default' =>0,
            'friendly_name' => 'Zaakceptowano trenerów'
        ),
        'timesheet_accepted' => array(
            'type' => 'boolean',
            'default' => 0,
            'friendly_name' => 'Zaakceptowano listę obecności'
        ),
        'accept_timesheet_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),

        'accept_schedule_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'start_casting_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'close_casting_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'accept_participation_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'assign_trainers_to_modules_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'mark_as_realized_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'choose_training_location_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'choose_materials_manager_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'send_order_catering_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'send_order_materials_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'order_catering_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'order_materials_task_id' => array(
            'type' => 'integer',
            'default' => 0
        ),
        'locked_module_timesheet' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Lista obecności',
            'default' => '1'
        ),
        'locked_module_participants' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Uczestnicy',
            'default' => '1'
        ),
        'locked_module_start_casting' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Uruchom casting',
            'default' => '1'
        ),
        'locked_module_end_casting' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Zakończ casting',
            'default' => '1'
        ),
        'locked_module_trainers_settlement' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Rozliczenia trenerów',
            'default' => '1'
        ),
        'locked_module_mark_as_realized' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Oznacz jako zrealizowany',
            'default' => '1'
        ),
        'locked_module_training_materials' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Materiały szkoleniowe',
            'default' => '1'
        ),
        'locked_module_catering' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Catering',
            'default' => '1'
        ),
        'locked_module_registration' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zablokowany moduł Elektroniczna rejestracja',
            'default' => '1'
        ),
        'realized' => array(
            'type' => 'boolean',
            'friendly_name' => 'Zrealizowany',
            'available_values' => array(
                '0' => 'Nie',
                '1' => 'Tak'
            ),
            'default' => '0'
        ),
        'materials_delivering_date' => array(
            'type' => 'date',
            'friendly_name' => 'Sugerowana data realizacji',
            'can_be_null' =>true,
        ),
        'materials_manager_id' => array(
            'type' => 'integer',
            'index' => 'multi',
            'default' => 0,
            'validate' => array(
                'presence'
            )
        ),
        'catering_delivering_date' => array(
            'type' => 'date',
            'friendly_name' => 'Sugerowana data realizacji',
            'can_be_null' => true
        ),
        'ordered_binders_amount' => array(
            'type' => 'integer',
            'default' => 0,
            'friendly_name' => 'Liczba zamówionych segregatorów'
        ),

        'fee_per_hour' => array(
            'type' => 'real',
            'default' => 100,
            'data_type' => 'money'

        ),
        'exam_fee_per_hour' => array(
            'type' => 'real',
            'default' => 250,
            'data_type' => 'money'

        ),
        'trip_fee_per_km' => array(
            'type' => 'real',
            'default' => 0.6,
            'data_type' => 'money'

        ),
        'product_branch' => array(
            'type'          => 'string',
            'friendly_name' => 'Branża',
        ),
    );

    protected static $named_scopes = array(
        'assigned_to_guardian_id'   => '`products`.`id` IN (SELECT `product_guardians`.`product_id` FROM `product_guardians` WHERE `product_guardians`.`guardian_id` = %0)',
        'has_schedules'             => '`products`.`id` IN (SELECT `products_schedule`.`product_id` FROM `products_schedule` WHERE `products_schedule`.`id` IN %0)',
        'has_participant'           => '`products`.`id` IN (SELECT `participant_assignments`.`product_id` FROM `participant_assignments` WHERE `participant_assignments`.`participant_id` = %0)',
        'has_trainer'               => '`products`.`id` IN (SELECT `product_module_trainers`.`product_id` FROM `product_module_trainers` WHERE `product_module_trainers`.`trainer_id` = %0)',

        'planned'                   => 'start_date > now()',
        'ongoing'                   => 'start_date <= now() AND end_date >= now()',
        'finished'                  => 'end_date < now()',

        'between_dates'             => 'start_date BETWEEN %0 AND %1 OR end_date BETWEEN %0 AND %1',

        'has_accepted_participants_from_company' => 'products.id IN (
            SELECT products_schedule.product_id FROM products_schedule
            JOIN module_participant ON module_participant.schedule_id = products_schedule.id
            JOIN participant_assignments ON participant_assignments.participant_id = module_participant.participant_id AND products_schedule.product_id = participant_assignments.product_id
            WHERE participant_assignments.company_branch_id IN (
                SELECT company_branches.id FROM company_branches WHERE company_branches.company_id = %0
            )
            AND module_participant.is_accepted = 1
        )',
    );

    protected static $grammar = array(
        'nominative' => array(
            'singular' => 'produkt',
        ),
    );

    protected function  _initialize()
    {
        $this->setProductCallbacks();
    }

    public function validate_parent_project()
    {
        if ($this->product_type_id == 5 && ( ! $this->project || $this->project->type != 2))
        {
            $this->addError('project', 'Dla produktów europejskich konieczne jest wybranie projektu nadrzędnego z typem europejskim');
        }
    }

    public function validate_dates()
    {
        if ($this->start_date > $this->end_date)
        {
            $this->addError('end_date', 'Data zakończenia nie może być wcześniejsza niż data rozpoczęcia');
        }
    }

    public function validate_type()
    {
        if($this->id)
        {
            $old_product = \App\Models\Product::find($this->id);
            if ($old_product->product_type != $this->product_type)
            {
                $this->addError('product_type', 'Nie możesz zmienić typu raz utworzonego projektu');
            }
        }
    }

    public function assign_participant($participant, $branch = null)
    {
        if ($branch == null)
        {
            $branch = $participant->company_branch;
        }
        $assigment = new \App\Models\ParticipantAssignment();
        $assigment->participant = $participant;
        $assigment->product = $this;
        $assigment->branch = $branch;
        $assigment->save();
    }

    protected function _beforeValidation()
    {
        $this->validate_dates();
        $this->validate_parent_project();
        $this->validate_type();
    }

    public function copyModulesFromOffer($offer)
    {
        $scopes = $offer->scopes();

        foreach ($scopes as $scope)
        {
            $this->copyModulesFromScope($scope);
        }
    }

    public function copyModulesFromScope($scope)
    {
        $modules = $scope->modules(array(), TRUE);

        foreach ($modules->all() as $module)
        {
            $this->copyModule($module, $scope);
        }
    }

    public function copyModule($module, $scope = null)
    {
        $existing_modules = \App\Models\Products\ProductModules::all(array(), TRUE)
                                                    ->module_id_is_eq($module->id)
                                                    ->product_id_is_eq($this->id)
                                                    ->count();
        if ($existing_modules)
        {
             \MP\JS\pushMessage('Moduł '.$module->number.' nie został zaimportowany. Jest już przypisany do bieżącego produktu.');
             return FALSE;
        }


        $product_modules = \App\Models\Products\ProductModules::build($this, $module, $scope);
        $product_modules->save();
    }

    public function assignPlace($place)
    {
        $product_places = new \App\Models\Products\ProductPlace();
        $product_places->product_id = $this->id;
        $product_places->place_id   = $place->id;
        $product_places->save();
    }

    public function getMainGuardian()
    {
          return $this->main_guardian(array(), TRUE)->first();
    }

    public function setNewMainGuardian($guardian)
    {
        $product_guardian = \App\Models\ProductGuardian::product_id_is_eq($this)->is_main_is_eq(1)->first();

        if(is_object($product_guardian))
        {
            $product_guardian->is_main = 0;
            $product_guardian->save(TRUE);
        }

        $product_guardian = \App\Models\ProductGuardian::guardian_id_is_eq($guardian)->product_id_is_eq($this)->first();

        if(is_object($product_guardian))
        {
            $product_guardian->is_main = 1;
            $product_guardian->save(TRUE);
        }
    }

    public function createProductMainGuardian()
    {
        $current_user = \App\Models\User::$current;
        $product_guardian = \App\Models\ProductGuardian::build($this, $current_user);
        $product_guardian->is_main = 1;
        $product_guardian->save();
    }

    public function trainersAssignedToModules()
    {
        $trainers_ids = array_map(function ($module_trainer) {
            return $module_trainer->trainer_id;
        }, \App\Models\Products\ModuleTrainer::product_id_is_eq($this->id)->all());
        return \App\Models\Users\Trainer::id_is_in($trainers_ids);
    }

    public function updateHoursCount()
    {
        $this->hours_count = 0;

        foreach($this->schedule_days as $schedule_day)
        {
            foreach ($schedule_day->schedules as $schedule)
            {
                $this->hours_count += $schedule->duration_time;
            }
        }
        $this->save();
    }

    public function importTrainingPlace($branch, $save = TRUE)
    {
        $this->place_name                   = $branch->name;
        $this->address                      = $branch->street. ' '.$branch->house_number.' '.$branch->door_number;
        $this->postcode                     = $branch->postcode;
        $this->city                         = $branch->city;
        $this->city_locative_case           = $branch->city_locative_case;
        $this->map_link                     = $branch->map_link;
        $this->state_id                     = $branch->administrative_unit?$branch->administrative_unit->parent->id:0;
        $this->contact_person_first_name    = $branch->main_contact_person?$branch->main_contact_person->first_name:'';
        $this->contact_person_last_name     = $branch->main_contact_person?$branch->main_contact_person->last_name:'';
        $this->phone                        = $branch->contact_phone;

        if ($save)
            $this->save(TRUE);
    }

    protected function _afterCreate()
    {
        $this->createProductMainGuardian();

        $this->triggerActionCallbacks(self::ACTION_PRODUCT_STARTED);
    }

    private function getMapLinkForAddress()
    {
        $address_query = $this->address . ", " . $this->city;
        $image_url = 'http://maps.google.com/maps/api/staticmap?center='.$address_query.'&zoom=15&size=800x400&markers=size:mid|color:red|label:Tu|'.$address_query.'&sensor=false';
        return "<img src='".$image_url."'/>";
    }

    public function assignTrainerToCasting($trainer, $additional_data)
    {

       $casting = \App\Models\Products\ProductTrainer
                                                ::all(array(), TRUE)
                                                ->trainer_id_is_eq($trainer->id)
                                                ->product_id_is_eq($this->id)
                                                ->first();

       if (!$casting)
            $casting = new \App\Models\Products\ProductTrainer();

       $casting->product_id = $this->id;
       $casting->trainer_id = $trainer->id;

       $casting->interests  = isset($additional_data['interests'])?$additional_data['interests']:array();
       $casting->assigments = isset($additional_data['assigments'])?$additional_data['assigments']:array();
       $casting->info       = isset($additional_data['info'])?$additional_data['info']:'';


      return $casting->save(TRUE);
    }


    public function unassignTrainerCasting($trainer)
    {

       $casting = \App\Models\Products\ProductTrainer
                                                ::all(array(), TRUE)
                                                ->trainer_id_is_eq($trainer->id)
                                                ->product_id_is_eq($this->id)
                                                ->first();
       if (!$casting)
            FALSE;

       return $casting->destroy();
    }

    protected function _beforeSave()
    {
        if($this->id)
        {
            $this->old_project_id = \App\Models\Product::find($this->id)->project_id;
        }

        if ($this->isTouched('participant_has_company'))
        {
            if ($this->participant_has_company)
            {
                foreach ($this->participations as $participation)
                    $participation->destroy();
            }
            else
            {
                foreach ($this->company_assignments as $company_assignment)
                    $company_assignment->destroy();
            }
        }

        if ($this->copy_address_from_branch && ($this->isTouched('copy_address_from_branch')  || $this->isTouched('address_branch_id')) )
        {
            if ($this->address_branch_id)
            {

                $branch = CompanyBranch::find($this->address_branch_id);
                if ($branch)
                    $this->importTrainingPlace($branch, FALSE);
            }
        }

        if(!$this->map_link)
        {
            $this->map_link = $this->getMapLinkForAddress();
        }
    }

    protected function _afterSave()
    {
        if ($this->isProductTypeEuropean())
        {
            if ($this->isTouched('start_date') || $this->isTouched('end_date')) {
                $this->project->setDates();
            }

            if ($this->isTouched('project_id')) {

                if($this->old_project_id != $this->project_id)
                {
                    $this->project_change();
                }
            }
        }
    }

    public function project_change()
    {
        $participations = \App\Models\ProjectParticipation::project_id_is_eq($this->old_project)->all();
        foreach($participations as $participation) {
            $new_participation = clone $participation;
            $new_participation->project_id = $this->project_id;
            $new_participation->save();
        }
        if($this->old_project->products(array(), true)->count() == 0) {
            foreach($participations as $participation) {
                $participation->destroy();
            }
        }

        $this->project->setDates();
        $this->old_project->setDates();
    }

    public function isProductTypeCompany()
    {
        return $this->product_type(array(), TRUE)->is_company_type()->count();
    }

    public function isProductTypeEuropean()
    {
        if(!$this->project) {
            return false;
        }
        return $this->product_type(array(), TRUE)->is_european_type()->count();
    }

    public function getColor()
    {
        return '#'.strtr(substr(md5($this->id), 26), array('c'=>'6', 'd'=>'7', 'e'=>'8', 'f'=>'9'));
    }

    public static function company_required($company, $object = NULL)
    {
        if ($object)
        {
            if ($object->isProductTypeCompany())
                return TRUE;
        }
        return FALSE;
    }

    public function activateCasting($params)
    {
        $this->casting_status = 1;
        $this->casting_end_date     = isset($params['casting_end_date'])?$params['casting_end_date']:NULL;
        $this->fee_per_hour         = isset($params['fee_per_hour'])?$params['fee_per_hour']:NULL;
        $this->exam_fee_per_hour    = isset($params['exam_fee_per_hour'])?$params['exam_fee_per_hour']:NULL;
        $this->trip_fee_per_km      = isset($params['trip_fee_per_km'])?$params['trip_fee_per_km']:NULL;

        $this->save(TRUE);

        $this->triggerActionCallbacks(self::ACTION_CASTING_STARTED);
    }

    protected function setProductCallbacks()
    {
        //=================================================

        $this->addActionCallback(self::ACTION_PRODUCT_STARTED, function ($product) {

            //sporządzić i zaakceptować harmonogram produktu
            $schedule_task = new Models\Task();
            $schedule_task->product_id = $product->id;
            $schedule_task->message = 'Sporządzić i zaakceptować harmonogram produktu';
            $schedule_task->type = Models\Task::TYPE_EXECUTABLE;
            $schedule_task->link_type = Models\Task::LINK_TYPE_ACCEPT_SCHEDULE;
            $schedule_task->realization_planned_date = date('Y-m-d');
            $schedule_task->sender_id = User::$current->id;
            $schedule_task->save();

            $product->accept_schedule_task_id = $schedule_task->id;

            //wybrać miejsca szkoleniowe i noclegowe
            $schedule_task = new Models\Task();
            $schedule_task->product_id = $product->id;
            $schedule_task->message = 'Wybrać miejsca szkoleniowe i noclegowe';
            $schedule_task->realization_planned_date = date('Y-m-d');
            $schedule_task->sender_id = User::$current->id;
            $schedule_task->save();

            $product->choose_training_location_task_id = $schedule_task->id;

            $product->save();

        });

        //==================================================

        //odblokować moduł uczestnicy modułów i start castingu
        $this->addActionCallback(self::ACTION_SCHEDULE_ACCEPTED, function ($product) {
            $product->locked_module_participants = 0;
            $product->locked_module_start_casting = 0;
            $product->locked_module_registration = 0;
            $product->save();

            $accept_schedule_task = Task::find($product->accept_schedule_task_id, false);
            $accept_schedule_task->markAsCompleted();

            //zadania

            $task = new Models\Task();
            $task->product_id = $product->id;
            $task->message = 'Sporządzić i zaakceptować listę uczestników modułów';
            $task->type = Models\Task::TYPE_EXECUTABLE;
            $task->link_type = Models\Task::LINK_TYPE_ACCEPT_PARTICIPATION;
            $task->realization_planned_date = date('Y-m-d');
            $task->sender_id = User::$current->id;
            $task->save();

            $product->accept_participation_task_id = $task->id;

            $task = new Models\Task();
            $task->product_id = $product->id;
            $task->message = 'Uruchomić casting trenerów';
            $task->type = Models\Task::TYPE_EXECUTABLE;
            $task->link_type = Models\Task::LINK_TYPE_START_CASTING;
            $task->realization_planned_date = date('Y-m-d');
            $task->sender_id = User::$current->id;
            $task->save();

            $product->start_casting_task_id = $task->id;
            $product->save();
        });

        //======================================================

        $this->addActionCallback(self::ACTION_PARTICIPATION_ACCEPTED, function ($product) {

            $accept_participation_task = Task::find($product->accept_participation_task_id, false);
            if($accept_participation_task)  {
                $accept_participation_task->markAsCompleted();
            }

            //dodajemy taski aby zamówić materiały szkoleniowe i catering

            $task = new Task();
            $task->product_id = $product->id;
            $task->message = 'Zamówić materiały szkoleniowe';
            $task->type = Models\Task::TYPE_EXECUTABLE;
            $task->link_type = Models\Task::LINK_TYPE_SEND_ORDER_TRAINING_MATERIALS;
            $task->realization_planned_date = date('Y-m-d');
            $task->sender_id = User::$current->id;
            $task->save();

            $product->send_order_materials_task_id = $task->id;

            $task = new Task();
            $task->product_id = $product->id;
            $task->message = 'Zamówić catering';
            $task->type = Models\Task::TYPE_EXECUTABLE;
            $task->link_type = Models\Task::LINK_TYPE_SEND_ORDER_CATERING;
            $task->realization_planned_date = date('Y-m-d');
            $task->sender_id = User::$current->id;
            $task->save();

            $product->send_order_catering_task_id = $task->id;

            if($product->product_type_id == 5)
            {
                $task = new Task();
                $task->product_id = $product->id;
                $task->message = 'Wypełnić i zaakceptować listę obecności';
                $task->type = Models\Task::TYPE_EXECUTABLE;
                $task->link_type = Models\Task::LINK_TYPE_ACCEPT_TIMESHEET;
                $task->realization_planned_date = date('Y-m-d');
                $task->sender_id = User::$current->id;
                $task->save();

                $product->accept_timesheet_task_id = $task->id;
            }

            $product->locked_module_training_materials = 0;
            $product->locked_module_catering = 0;
            $product->locked_module_timesheet = 0;
            $product->save();


            if ($product->hasExamModule())  {
                Messenger::getInstance()->addMessage(Message::TYPE_EXAM_MATERIALS_NEEDED, $product);
            }


            //zamówienia dotyczące kateringu zostanie utworzone po wysłaniu wiadomości
            Messenger::getInstance()->addMessage(Message::TYPE_PARTICIPATION_CONFIRMATION, $product);

        });

        $this->addActionCallback(self::ACTION_PARTICIPATION_CHANGED, function($product){
            Messenger::getInstance()->addMessage(Message::TYPE_PARTICIPATION_CHANGED, $product);
            Messenger::getInstance()->addMessage(Message::TYPE_SCHEDULE_CHANGED, $product);
        });

        $this->addActionCallback(self::ACTION_SCHEDULE_CHANGED, function($product){
            Messenger::getInstance()->addMessage(Message::TYPE_SCHEDULE_CHANGED, $product);
        });

        //=====================================================
        $this->addActionCallback(self::ACTION_CASTING_STARTED, function($product){
            $product->locked_module_end_casting = 0;
            $product->save();

            $start_casting_task = Task::find($product->start_casting_task_id, false);
            if($start_casting_task) {
                $start_casting_task->markAsCompleted();
            }

            Messenger::getInstance()->addMessage(Message::TYPE_CASTING_START, $product);

            if(!$product->assign_trainers_to_modules_task_id)
            {
                $task = new Models\Task();
                $task->product_id = $product->id;
                $task->message = 'Sporządzić i zaakceptować listę trenerów';
                $task->type = Models\Task::TYPE_EXECUTABLE;
                $task->link_type = Models\Task::LINK_TYPE_ACCEPT_TRAINERS;
                $task->realization_planned_date = date('Y-m-d');
                $task->sender_id = User::$current->id;
                $task->save();

                $product->assign_trainers_to_modules_task_id = $task->id;
            }

            if(!$product->close_casting_task_id)
            {
                $task = new Models\Task();
                $task->product_id = $product->id;
                $task->message = 'Zamknąć casting trenerów';
                if($product->casting_end_date)  {
                    $task->realization_planned_date = $product->casting_end_date;
                }
                else    {
                    $task->realization_planned_date = date('Y-m-d');
                }
                $task->type = Models\Task::TYPE_EXECUTABLE;
                $task->link_type = Models\Task::LINK_TYPE_CLOSE_CASTING;
                $task->sender_id = User::$current->id;
                $task->save();

                $product->close_casting_task_id = $task->id;
            }
            $product->save();
        });

        //======================

        $this->addActionCallback(self::ACTION_CASTING_CLOSED, function ($product) {
            $close_casting_task = Task::find($product->close_casting_task_id, false);
            if($close_casting_task) {
                $close_casting_task->markAsCompleted();
            }


            Messenger::getInstance()->addMessage(Message::TYPE_CASTING_CLOSED, $product);
            Messenger::getInstance()->addMessage(Message::TYPE_PRODUCT_INFO, $product);

            if(!$product->mark_as_realized_task_id)
            {
                $task = new Models\Task();
                $task->product_id = $product->id;
                $task->message = 'Oznaczyć produktu jako zrealizowany';
                $task->type = Task::TYPE_EXECUTABLE;
                $task->link_type = Task::LINK_TYPE_MARK_AS_REALIZED;
                $task->realization_planned_date = date('Y-m-d');
                $task->sender_id = User::$current->id;
                $task->save();

                $product->mark_as_realized_task_id = $task->id;
            }
            $product->locked_module_mark_as_realized = 0;
            $product->save();
        });

        //===========================

        $this->addActionCallback(self::ACTION_PRODUCT_REALIZED, function ($product) {
            $product->locked_module_trainers_settlement = 0;
            $product->save();

            $mark_as_realized_task = Task::find($product->mark_as_realized_task_id, false);
            if ($mark_as_realized_task)  {
                $mark_as_realized_task->markAsCompleted();
            }

            $task = new Models\Task();
            $task->product_id = $product->id;
            $task->message = 'Rozliczyć trenerów';
            $task->type = Models\Task::TYPE_EXECUTABLE;
            $task->link_type = Models\Task::LINK_TYPE_SETTLE_TRAINERS;
            $task->realization_planned_date = date('Y-m-d');
            $task->sender_id = User::$current->id;
            $task->save();

            $task = new Models\Task();
            $task->product_id = $product->id;
            $task->message = 'Wysłać do RID informacje o szkoleniu';
            $task->realization_planned_date = date('Y-m-d');
            $task->sender_id = User::$current->id;
            $task->save();


            $task = new Models\Task();
            $task->product_id = $product->id;
            $task->message = 'Przygotować pismo "Podziękowanie za udział w szkoleniu"';
            $task->realization_planned_date = date('Y-m-d');
            $task->sender_id = User::$current->id;
            $task->save();
        });

        //===============================

        $this->addActionCallback(self::ACTION_TRAINERS_ASSIGNMENT_ACCEPTED, function ($product) {
            $assign_trainers_to_modules_task = Task::find($product->assign_trainers_to_modules_task_id, false);
            if ($assign_trainers_to_modules_task) {
                $assign_trainers_to_modules_task->markAsCompleted();
            }

            $product_module_trainers = \App\Models\Products\ModuleTrainer
                    ::product_id_is_eq($product->id)
                    ->all();
            foreach ($product_module_trainers as $product_module_trainer)
            {
                $schedules = \App\Models\Products\ProductSchedule
                        ::product_id_is_eq($product->id)
                        ->module_id_is_eq($product_module_trainer->module_id)
                        ->all();
                $dates = array();
                foreach ($schedules as $schedule)
                    $dates[] = $schedule->schedule_day->date;
                $date = new \DateTime(\max($dates));
                $date = $date->add(new \DateInterval('P1W'))->format('Y-m-d');
                $task = new Models\Task();
                $task->user_id = $product_module_trainer->trainer_id;
                $task->product_id = $product->id;
                $task->message = 'Rozliczyć się z modułu '.$product_module_trainer->module->number;
                $task->realization_planned_date = $date;
                $task->sender_id = User::$current->id;
                $task->save();
            }
        });

        //======================

        $this->addActionCallback(self::ACTION_TRAINING_LOCATION_ACCEPTED, function ($product) {
            $choose_training_location_task = Task::find($product->choose_training_location_task_id);
            if ($choose_training_location_task)  {
                $choose_training_location_task->markAsCompleted();
            }
        });

        //=====================

        $this->addActionCallback(self::ACTION_CATERING_ORDER_SENT, function ($product) {
            $send_order_task = $product->send_order_catering_task();
            $send_order_task->markAsCompleted();

            $message_template = Messenger::getInstance()->addMessage(Message::TYPE_CATERING_ORDERED, $product);
            $message_template->send();
        });

        //=====================

        $this->addActionCallback(self::ACTION_MATERIALS_ORDER_SENT, function ($product) {
            $send_order_task = $product->send_order_materials_task();
            $send_order_task->markAsCompleted();

            $message_template = Messenger::getInstance()->addMessage(Message::TYPE_MATERIALS_ORDERED, $product);
            $message_template->send();
        });

        //=====================

        $this->addActionCallback(self::ACTION_CATERING_ORDERED, function ($product) {
            $order_catering_task = $product->order_catering_task;
            if($order_catering_task)    {
                $order_catering_task->markAsCompleted();
            }
        });

        //==================

        $this->addActionCallback(self::ACTION_MATERIALS_ORDERED, function ($product) {
            $order_materials_task = $product->order_materials_task;
            if($order_materials_task)    {
                $order_materials_task->markAsCompleted();
            }
        });

        //==================

        $this->addActionCallback(self::ACTION_TIMESHEET_ACCEPTED, function ($product) {
            $product->timesheet_accepted = 1;
            $product->save();

            $task = Task::find($product->accept_timesheet_task_id, false);
            if($task)
            {
                $task->markAsCompleted();
            }
        });

    }

    public function get_name_pl($value)
    {
        return \strip_tags($value);
    }

    public function get_name_de($value)
    {
        return \strip_tags($value);
    }

    public function get_name_en($value)
    {
        return \strip_tags($value);
    }


    public function orderMaterials($materials_manager, $materials_delivering_date)
    {
        if ($this->materials_order_date == '0000-00-00')
        {
            $this->materials_order_date = \date('Y-m-d');
            $this->save();

            \App\Models\Task::create(
                    $materials_manager,
                    $this,
                    'Dostarczyć materiały szkoleniowe',
                    $materials_delivering_date,
                    \App\Models\User::$current);

            \MP\JS\pushMessage('Zamówienie na materiały zostało złożone');
        }
        else
        {
            $task = \App\Models\Task
                    ::product_id_is_eq($this->id)
                    ->message_is_eq('Dostarczyć materiały szkoleniowe')
                    ->first();

            $task->realization_planned_date = $this->materials_delivering_date;
            $task->user_id = $this->materials_manager_id;
            $task->sender->id = \App\Models\User::$current->id;
            $task->realization_planned_date = $this->materials_delivering_date;
            $task->save();

            \MP\JS\pushMessage('Zamówienie na materiały zostało zmienione');
        }
    }

    public function getHoursDuration()
    {
        $duration = 0;

        foreach ($this->schedules as $product_schedule)
        {
            $duration += $product_schedule->duration_time;
        }
        return $duration;
    }

    public function getPlaceAddress()
    {
        return $this->address.' '.$this->postcode.' '.$this->city.' '.$this->state;
    }

    public function hasExamModule()
    {
        foreach($this->modules() as $module)
        {
            if ($module->is_exam) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function getParticipantsHoursCount()
    {
        $total_hours = 0;
        foreach ($this->participations as $participation) {
            $module_participations = \App\Models\Products\ModuleParticipant
                    ::schedule_id_is_in($this->schedules)
                    ->participant_id_is_eq($participation->participant_id)
                    ->all();
            $hours = 0;
            foreach ($module_participations as $module_participation) {
                $hours += $module_participation->schedule->duration_time;
            }
            $total_hours += $hours;
        }
        return $total_hours;
    }
}

