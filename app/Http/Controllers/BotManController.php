<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;


class BotManController extends Controller
{
    /**
     * Handle BotMan requests.
     */
    public function handle()
    {
        $botman = app('botman');

        $botman->hears('.*', function ($botman) {
            $botman->startConversation(new ReservationConversation());
        });

        $botman->listen();
    }
}

class ReservationConversation extends Conversation
{
    protected $name;
    protected $email;
    protected $matricule;
    protected $intitule_reunion;
    protected $animateur;
    protected $date_reunion;
    protected $heure_debut;
    protected $number_participant;
    protected $meeting_room;

    /**
     * Start the conversation.
     */
    public function run()
    {
        $this->salutation();
    }

    public function salutation()
{
    $question = Question::create('Bonjour! Que souhaitez-vous faire aujourd\'hui?')
        ->fallback('Unable to ask for action')
        ->callbackId('ask_action')
        ->addButtons([
            Button::create('Consulter les données d\'une réunion')->value('view_meetings'),
            Button::create('Réserver une salle pour une réunion')->value('book_room'),
            Button::create('Envoyer des rappels')->value('send_reminders'),
        ]);

    $this->ask($question, function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
            $action = $answer->getValue();
            if ($action === 'view_meetings') {
                $this->askDisplayOption();
            } elseif ($action === 'book_room') {
                $this->startBookingProcess();
            } elseif ($action === 'send_reminders') {
                $this->sendReminders();
            }
        } else {
            $this->say("Please choose one of the options.");
            $this->repeat();
        }
    });
}



    public function startBookingProcess()
    {
        
        $this->say("OK, let's start the room reservation process...");
        
        $this->askName();
    }

    public function askName()
    {
        $this->ask('Hello! What is your name?', function (Answer $answer) {
            $this->name = $answer->getText();
            $this->say('Nice to meet you ' . $this->name);
            $this->askEmail();
        });
    }

    public function askEmail()
    {
        $this->ask('Please What is your email?', function (Answer $answer) {
            $this->email = $answer->getText();
            $this->askMatricule();
        });
    }

    public function askMatricule()
    {
        $this->ask('Please What is your matricule ?', function (Answer $answer) {
            $this->matricule = $answer->getText();
            $this->askIntituleReunion();
        });
    }

    public function askIntituleReunion()
    {
        $this->ask('What is the title of the meeting?', function (Answer $answer) {
            $this->intitule_reunion = $answer->getText();
            $this->askAnimateur();
        });
    }

    public function askAnimateur()
    {
        $this->ask('Who is the meeting moderator ?', function (Answer $answer) {
            $this->animateur = $answer->getText();
            $this->askDate_reunion();
        });
    }

    public function askDate_reunion()
    {
        $this->ask('When will the meeting be held? Please provide the date (e.g., YYYY-MM-DD)', function (Answer $answer) {
            $dateInput = $answer->getText();

            // Regular expression to match a date in the format YYYY-MM-DD
            if (preg_match('/\b\d{4}-\d{2}-\d{2}\b/', $dateInput, $matches)) {
                $this->date_reunion = $matches[0];
                $this->askHeure_debut();
            } else {
                $this->say('Sorry, I didn\'t recognize that as a valid date format. Please provide the date in YYYY-MM-DD format.');
                $this->repeat(); // Repeat the question
            }
        });
    }

    public function askHeure_debut()
    {
        $this->ask('What time will the meeting start? Please provide the time (e.g., HH:MM)', function (Answer $answer) {
            $timeInput = $answer->getText();

            // Regular expression to match a time in the format HH:MM
            if (preg_match('/\b\d{2}:\d{2}\b/', $timeInput, $matches)) {
                $this->heure_debut = $matches[0];
                $this->askNumber_participant();
            } else {
                $this->say('Sorry, I didn\'t recognize that as a valid time format. Please provide the time in HH:MM format.');
                $this->repeat(); // Repeat the question
            }
        });
    }

    public function askNumber_participant()
    {
        $this->ask('How many participant will attend the meeting ?', function (Answer $answer) {
            $this->number_participant = $answer->getText();
            $this->askMeeting_room();
        });
    }

    public function askMeeting_room()
{
    $question = Question::create('Which room would you like to reserve?')
        ->fallback('Unable to ask for meeting room')
        ->callbackId('ask_meeting_room')
        ->addButtons([
            Button::create('Conference room main building')->value('Conference room main building'),
            Button::create('Conference room annex building')->value('Conference room annex building'),
            Button::create('Conference room minister\'s office')->value('Conference room minister\'s office'),
        ]);

    $this->ask($question, function (Answer $answer) {
        // Detect if a button was clicked:
        if ($answer->isInteractiveMessageReply()) {
            $this->meeting_room = $answer->getValue();
            $this->askConfirmation();
        } else {
            $this->say("Please choose one of the options.");
            $this->repeat();
        }
    });
}



    public function askConfirmation()
    {
        $message = "Here are the details you provided:\n";
        $message .= "Name: " . $this->name . "\n";
        $message .= "Email: " . $this->email . "\n";
        $message .= "Matricule: " . $this->matricule . "\n";
        $message .= "Meeting Title: " . $this->intitule_reunion . "\n";
        $message .= "Moderator: " . $this->animateur . "\n";
        $message .= "Meeting Date: " . $this->date_reunion . "\n";
        $message .= "Start Time: " . $this->heure_debut . "\n";
        $message .= "Number of Participants: " . $this->number_participant . "\n";
        $message .= "Meeting Room: " . $this->meeting_room . "\n";
        $message .= "Please confirm if these details are correct by responding with 'yes' or 'no'.";
    
        $this->ask($message, function(Answer $answer) {
            $response = strtolower($answer->getText());
    
            if ($response === 'yes') {
                try {
                    // Enregistrement des données dans la base de données
                    DB::table('meeting')->insert([
                        'name' => $this->name,
                        'email' => $this->email,
                        'matricule' => $this->matricule,
                        'intitule_reunion' => $this->intitule_reunion,
                        'animateur' => $this->animateur,
                        'date_reunion' => $this->date_reunion,
                        'heure_debut' => $this->heure_debut,
                        'number_participant' => $this->number_participant,
                        'meeting_room' => $this->meeting_room,
                    ]);
    
                    // Send email to admin
                    $this->sendEmailWithPHPMailer(
                        $this->name,
                        $this->email,
                        $this->matricule,
                        $this->intitule_reunion,
                        $this->animateur,
                        $this->date_reunion,
                        $this->heure_debut,
                        $this->number_participant,
                        $this->meeting_room,
                        'mopouong.roberto@ictuniversity.edu.cm',
                        'New Meeting Reservation'
                    );
    
                    // Send email to user
                    $this->sendEmailWithPHPMailer(
                        $this->name,
                        $this->email,
                        $this->matricule,
                        $this->intitule_reunion,
                        $this->animateur,
                        $this->date_reunion,
                        $this->heure_debut,
                        $this->number_participant,
                        $this->meeting_room,
                        $this->email,
                        'Your Meeting Reservation'
                    );
    
                    $this->say("Your information has been successfully recorded and emailed.");
                } catch (\Exception $e) {
                    $this->say("There was an error recording your information or sending the email. Please try again." . $e->getMessage());
                }
            } elseif ($response === 'no') {
                $this->askWhichInformationToModify();
            } else {
                $this->say("Please respond with 'yes' or 'no'.");
                $this->repeat(); // Repeat the question
            }
        });
    }
    


    public function askWhichInformationToModify()
    {
        $this->ask("Which information would you like to modify? Please specify (e.g., 'name', 'email', 'meeting room')", function (Answer $answer) {
            $input = strtolower($answer->getText());

            switch ($input) {
                case 'name':
                    $this->askName();
                    break;
                case 'email':
                    $this->askEmail();
                    break;
                case 'matricule':
                    $this->askMatricule();
                    break;
                case 'meeting title':
                    $this->askIntituleReunion();
                    break;
                case 'moderator':
                    $this->askAnimateur();
                    break;
                case 'meeting date':
                    $this->askDate_reunion();
                    break;
                case 'start time':
                    $this->askHeure_debut();
                    break;
                case 'number of participants':
                    $this->askNumber_participant();
                    break;
                case 'meeting room':
                    $this->askMeeting_room();
                    break;
                default:
                    $this->say("Sorry, I didn't recognize that option.");
                    $this->askWhichInformationToModify(); // Repeat the question
                    break;
            }
        });
    }

    protected function sendEmailWithPHPMailer($name, $email, $matricule, $intitule_reunion, $animateur, $date_reunion, $heure_debut, $number_participant, $meeting_room, $recipient_email, $subject)
{
    

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'robertomopouong1@gmail.com';
        $mail->Password = 'ztonzmrrpamzknaz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('robertomopouong1@gmail.com', 'Meeting Booking Service');
        $mail->addAddress('mopouong.roberto@ictuniversity.edu.cm', 'Recipient Name');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Create the email body directly
        $body = "
            <h1>Meeting Reservation Details</h1>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Matricule:</strong> $matricule</p>
            <p><strong>Meeting Title:</strong> $intitule_reunion</p>
            <p><strong>Moderator:</strong> $animateur</p>
            <p><strong>Meeting Date:</strong> $date_reunion</p>
            <p><strong>Start Time:</strong> $heure_debut</p>
            <p><strong>Number of Participants:</strong> $number_participant</p>
            <p><strong>Meeting Room:</strong> $meeting_room</p>
        ";

        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
    } catch (Exception $e) {
        throw new \Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

public function askDisplayOption()
{
    $question = Question::create('How would you like to display the meetings?')
        ->fallback('Unable to ask for display option')
        ->callbackId('ask_display_option')
        ->addButtons([
            Button::create('By Date')->value('date'),
            Button::create('By Moderator')->value('moderator'),
        ]);

    $this->ask($question, function (Answer $answer) {
        if ($answer->isInteractiveMessageReply()) {
            $option = $answer->getValue();
            if ($option === 'date') {
                $this->displayMeetingsByDate();
            } elseif ($option === 'moderator') {
                $this->displayMeetingsByModerator();
            }
        } else {
            $this->say("Please choose one of the options.");
            $this->repeat();
        }
    });
}

public function displayMeetingsByDate()
{
    $this->ask('Please provide the date (e.g., YYYY-MM-DD) to display meetings:', function (Answer $answer) {
        $date = $answer->getText();

        // Query meetings from database based on date
        $meetings = DB::table('meeting')->where('date_reunion', $date)->get();

        if ($meetings->isEmpty()) {
            $this->say("No meetings found for the provided date.");
        } else {
            $this->displayMeetings($meetings);
        }
    });
}

public function displayMeetingsByModerator()
{
    $this->ask('Please provide the name of the moderator to display meetings:', function (Answer $answer) {
        $moderator = $answer->getText();

        // Query meetings from database based on moderator
        $meetings = DB::table('meeting')->where('animateur', $moderator)->get();

        if ($meetings->isEmpty()) {
            $this->say("No meetings found for the provided moderator.");
        } else {
            $this->displayMeetings($meetings);
        }
    });
}

public function displayMeetings($meetings)
{
    if ($meetings->isEmpty()) {
        $this->say("Aucune réunion trouvée.");
    } else {
        $this->say("Voici les réunions :");
        foreach ($meetings as $meeting) {
            $message = "Titre de la réunion : $meeting->intitule_reunion\n";
            $message .= "Animateur : $meeting->animateur\n";
            $message .= "Date de la réunion : $meeting->date_reunion\n";
            $message .= "Heure de début : $meeting->heure_debut\n";
            $message .= "Nombre de participants : $meeting->number_participant\n";
            $message .= "Salle de réunion : $meeting->meeting_room\n\n";
            $this->say($message);
        }
    }
}

public function sendReminders()
{
    // Récupération de la date actuelle
    $currentDate = date('Y-m-d');

    // Requête SQL pour récupérer les réunions prévues pour aujourd'hui
    $meetings = DB::table('meeting')
        ->join('personnel', 'meeting.animateur', '=', 'personnel.name_personnel')
        ->select('meeting.intitule_reunion', 'meeting.animateur', 'meeting.date_reunion', 'meeting.number_participant', 'meeting.meeting_room', 'personnel.mail_personnel')
        ->where('meeting.date_reunion', $currentDate)
        ->get();

    // Envoi des rappels par e-mail pour chaque réunion récupérée
    foreach ($meetings as $meeting) {
        $this->sendReminderEmail($meeting);
    }

    // Affichage d'un message de confirmation
    $this->say('Les rappels ont été envoyés avec succès!');
}

public function sendReminderEmail($meeting)
{
    // Construire le contenu de l'e-mail de rappel
    $subject = 'Rappel: Réunion prévue aujourd\'hui';
    $body = "Bonjour {$meeting->animateur},\n\nVous avez une réunion intitulée '{$meeting->intitule_reunion}' prévue aujourd'hui à {$meeting->heure_debut} dans la salle '{$meeting->meeting_room}'.\n\nCordialement,\nMinpostalk.";
    $mail = new PHPMailer(true);

    // Envoi de l'e-mail de rappel
    try {
        // Utilisation de PHPMailer pour envoyer l'e-mail
        // Assurez-vous de remplacer ces valeurs par vos propres paramètres SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'robertomopouong1@gmail.com';
        $mail->Password = 'ztonzmrrpamzknaz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('robertomopouong1@gmail.com', 'Minpostalk');
        $mail->addAddress($meeting->mail_personnel, $meeting->animateur);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
    } catch (Exception $e) {
        // Gérer les erreurs d'envoi d'e-mail
        $this->say("Une erreur s'est produite lors de l'envoi de l'e-mail de rappel à {$meeting->animateur}: {$mail->ErrorInfo}");
    }
}





}
