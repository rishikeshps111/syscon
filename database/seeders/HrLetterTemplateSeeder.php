<?php

namespace Database\Seeders;

use App\Models\HrLetterTemplate;
use Illuminate\Database\Seeder;

class HrLetterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'entity_type' => 'offer_letter',
                'language' => 'English',
                'template_name' => 'Standard Offer Letter',
                'subject' => 'Offer of Employment - {{ designation.name }}',
                'content' => '<p>Dear <strong>{{ employee.name }}</strong>,</p><p>We are pleased to offer you employment as <strong>{{ designation.name }}</strong>. Your proposed joining date is {{ employee.joining_date }}.</p><p>Your salary structure is:</p>{{ salary.components_table }}<p>Gross Salary: <strong>{{ salary.gross_salary }}</strong></p><p>Please confirm your acceptance of this offer.</p><p>Yours sincerely,<br>Human Resources</p>',
                'header_address' => 'Human Resources Department',
                'footer_content' => 'This is a system-generated offer letter.',
            ],
            [
                'entity_type' => 'warning_letter',
                'language' => 'English',
                'template_name' => 'Standard Warning Letter',
                'subject' => 'Warning Letter - {{ employee.name }}',
                'content' => '<p>Dear <strong>{{ employee.name }}</strong>,</p><p>This letter serves as a formal warning regarding the following matter:</p><p><strong>{{ warning.reason }}</strong></p><p>Incident Date: {{ warning.incident_date }}</p><p>You are required to provide your explanation by {{ warning.response_due_date }} and take immediate corrective action.</p><p>Yours sincerely,<br>Human Resources</p>',
                'header_address' => 'Human Resources Department',
                'footer_content' => 'This warning letter is confidential.',
            ],
            [
                'entity_type' => 'offer_letter',
                'language' => 'Hindi',
                'template_name' => 'मानक नियुक्ति प्रस्ताव पत्र',
                'subject' => 'नियुक्ति प्रस्ताव - {{ designation.name }}',
                'content' => '<p>प्रिय <strong>{{ employee.name }}</strong>,</p><p>हमें आपको <strong>{{ designation.name }}</strong> के पद पर नियुक्ति का प्रस्ताव देते हुए प्रसन्नता हो रही है। आपकी प्रस्तावित कार्यग्रहण तिथि {{ employee.joining_date }} है।</p><p>आपकी वेतन संरचना निम्नलिखित है:</p>{{ salary.components_table }}<p>सकल वेतन: <strong>{{ salary.gross_salary }}</strong></p><p>कृपया इस प्रस्ताव की स्वीकृति की पुष्टि करें।</p><p>सादर,<br>मानव संसाधन विभाग</p>',
                'header_address' => 'मानव संसाधन विभाग',
                'footer_content' => 'यह सिस्टम द्वारा निर्मित नियुक्ति प्रस्ताव पत्र है।',
            ],
            [
                'entity_type' => 'warning_letter',
                'language' => 'Hindi',
                'template_name' => 'मानक चेतावनी पत्र',
                'subject' => 'चेतावनी पत्र - {{ employee.name }}',
                'content' => '<p>प्रिय <strong>{{ employee.name }}</strong>,</p><p>यह पत्र निम्नलिखित विषय के संबंध में औपचारिक चेतावनी के रूप में जारी किया जा रहा है:</p><p><strong>{{ warning.reason }}</strong></p><p>घटना की तिथि: {{ warning.incident_date }}</p><p>आपसे {{ warning.response_due_date }} तक स्पष्टीकरण देने और तत्काल सुधारात्मक कार्रवाई करने की अपेक्षा की जाती है।</p><p>सादर,<br>मानव संसाधन विभाग</p>',
                'header_address' => 'मानव संसाधन विभाग',
                'footer_content' => 'यह चेतावनी पत्र गोपनीय है।',
            ],
            [
                'entity_type' => 'offer_letter',
                'language' => 'Telugu',
                'template_name' => 'ప్రామాణిక ఉద్యోగ నియామక పత్రం',
                'subject' => 'ఉద్యోగ నియామక ప్రతిపాదన - {{ designation.name }}',
                'content' => '<p>ప్రియమైన <strong>{{ employee.name }}</strong>,</p><p>మిమ్మల్ని <strong>{{ designation.name }}</strong> పదవికి నియమించడానికి మేము సంతోషిస్తున్నాము. మీ ప్రతిపాదిత చేరిక తేదీ {{ employee.joining_date }}.</p><p>మీ జీత నిర్మాణం:</p>{{ salary.components_table }}<p>స్థూల జీతం: <strong>{{ salary.gross_salary }}</strong></p><p>దయచేసి ఈ ప్రతిపాదనకు మీ అంగీకారాన్ని తెలియజేయండి.</p><p>భవదీయులు,<br>మానవ వనరుల విభాగం</p>',
                'header_address' => 'మానవ వనరుల విభాగం',
                'footer_content' => 'ఇది సిస్టమ్ రూపొందించిన ఉద్యోగ నియామక పత్రం.',
            ],
            [
                'entity_type' => 'warning_letter',
                'language' => 'Telugu',
                'template_name' => 'ప్రామాణిక హెచ్చరిక పత్రం',
                'subject' => 'హెచ్చరిక పత్రం - {{ employee.name }}',
                'content' => '<p>ప్రియమైన <strong>{{ employee.name }}</strong>,</p><p>కింది విషయానికి సంబంధించి ఈ అధికారిక హెచ్చరిక పత్రం జారీ చేయబడుతోంది:</p><p><strong>{{ warning.reason }}</strong></p><p>సంఘటన తేదీ: {{ warning.incident_date }}</p><p>{{ warning.response_due_date }} లోపు మీ వివరణను సమర్పించి, వెంటనే సరిదిద్దే చర్యలు తీసుకోవాలి.</p><p>భవదీయులు,<br>మానవ వనరుల విభాగం</p>',
                'header_address' => 'మానవ వనరుల విభాగం',
                'footer_content' => 'ఈ హెచ్చరిక పత్రం గోప్యమైనది.',
            ],
            [
                'entity_type' => 'offer_letter',
                'language' => 'Malayalam',
                'template_name' => 'സാധാരണ തൊഴിൽ വാഗ്ദാന കത്ത്',
                'subject' => 'തൊഴിൽ വാഗ്ദാനം - {{ designation.name }}',
                'content' => '<p>പ്രിയ <strong>{{ employee.name }}</strong>,</p><p><strong>{{ designation.name }}</strong> തസ്തികയിലേക്ക് നിങ്ങൾക്ക് നിയമന വാഗ്ദാനം നൽകുന്നതിൽ ഞങ്ങൾക്ക് സന്തോഷമുണ്ട്. നിർദ്ദേശിച്ച ജോലിയിൽ പ്രവേശിക്കുന്ന തീയതി {{ employee.joining_date }} ആണ്.</p><p>നിങ്ങളുടെ ശമ്പള ഘടന:</p>{{ salary.components_table }}<p>മൊത്ത ശമ്പളം: <strong>{{ salary.gross_salary }}</strong></p><p>ഈ വാഗ്ദാനത്തിന്റെ സ്വീകാര്യത ദയവായി സ്ഥിരീകരിക്കുക.</p><p>ആദരപൂർവ്വം,<br>മാനവ വിഭവശേഷി വിഭാഗം</p>',
                'header_address' => 'മാനവ വിഭവശേഷി വിഭാഗം',
                'footer_content' => 'ഇത് സിസ്റ്റം സൃഷ്ടിച്ച തൊഴിൽ വാഗ്ദാന കത്താണ്.',
            ],
            [
                'entity_type' => 'warning_letter',
                'language' => 'Malayalam',
                'template_name' => 'സാധാരണ മുന്നറിയിപ്പ് കത്ത്',
                'subject' => 'മുന്നറിയിപ്പ് കത്ത് - {{ employee.name }}',
                'content' => '<p>പ്രിയ <strong>{{ employee.name }}</strong>,</p><p>താഴെ പറയുന്ന വിഷയത്തിൽ ഔദ്യോഗിക മുന്നറിയിപ്പായാണ് ഈ കത്ത് നൽകുന്നത്:</p><p><strong>{{ warning.reason }}</strong></p><p>സംഭവ തീയതി: {{ warning.incident_date }}</p><p>{{ warning.response_due_date }}-നകം വിശദീകരണം നൽകുകയും ഉടൻ തിരുത്തൽ നടപടി സ്വീകരിക്കുകയും വേണം.</p><p>ആദരപൂർവ്വം,<br>മാനവ വിഭവശേഷി വിഭാഗം</p>',
                'header_address' => 'മാനവ വിഭവശേഷി വിഭാഗം',
                'footer_content' => 'ഈ മുന്നറിയിപ്പ് കത്ത് രഹസ്യമാണ്.',
            ],
        ];

        foreach ($templates as $template) {
            HrLetterTemplate::updateOrCreate(
                [
                    'entity_type' => $template['entity_type'],
                    'language' => $template['language'],
                    'template_name' => $template['template_name'],
                ],
                $template + ['is_active' => true]
            );
        }
    }
}
